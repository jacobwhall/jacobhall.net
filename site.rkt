#lang racket

; Thanks to Bogdan Popa for their helpful guide to racket's web server
; https://defn.io/2020/02/12/racket-web-server-guide/

(require db
         net/url
         web-server/servlet-env
         web-server/http
         web-server/http/request-structs
         web-server/templates
         web-server/web-server
         web-server/servlet-dispatch
         web-server/dispatch
         web-server/dispatchers/dispatch
         web-server/dispatchers/filesystem-map
         (prefix-in sequencer: web-server/dispatchers/dispatch-sequencer)
         (prefix-in files: web-server/dispatchers/dispatch-files)
         "../pass.rkt")

(define (http-200 content)
  (response/full
    200                  ; HTTP response code.
    #"OK"                ; HTTP response message.
    (current-seconds)    ; Timestamp.
    TEXT/HTML-MIME-TYPE  ; MIME type for content.
    '()                  ; Additional HTTP headers.
    (list                ; Content (in bytes) to send to the browser.
      (string->bytes/utf-8 content))))

(define (a key post)
  (cdr (assoc key post)))
      
(define (row->ass headers row)
  (map (λ (h r)
         make-hash
         (cons (cdr (first h))
               (if (sql-null? r)
                   ""
                   r)))
       headers (sequence->list row)))

; Required argument post-data is used in the post.txt template to render post
(define (ass->post post-data)
  (include-template "post.txt"))

; Required argument result is the output of PostgreSQL view "vPosts" selection
; e.g. from function "posts"
(define (rows-result->posts result)
  (foldr string-append ""
         (map (λ (r)
                (ass->post (row->ass (rows-result-headers result)
                                     r)))
              (rows-result-rows result))))

(define posts
  ; Required argument "limit" 
  (λ (limit
      ; Optional argument only returns specific author
      ; TODO: use Microformats standard author UID rather than name
      #:author [author "Jacob Hall"]
      #:types [types (list 1 2 3 4 5 6 7 8 9 10 11 12)])
    ; Convert the result of PostgreSQL view "vPosts" into embeddable HTML
    (rows-result->posts
                       (query
                        pgc
                        (prepare pgc "SELECT *
                                      FROM vPosts
                                      WHERE author = $2
                                      AND type=ANY($3::int[])
                                      LIMIT $1")
                        limit
                        author
                        types))))

; The homepage gets its own template
(define (homepage req)
  (http-200 (include-template "index.txt")))

; Articles get their own template
(define (article title content)
  (http-200 (include-template "article.txt")))

; TODO: proper HTTP response code!
; Output for 404 error
(define (not-found req)
  (article "404" "<h1>Not Found</h1>")) ; TODO: use xexpr, or template

; Dispatcher for rule-based pages like homepage and /kind
(define-values (main-dispatcher _)
  (dispatch-rules
   [("") homepage]
   [("all") (λ (r)
               (article "all posts"
                        (posts 25)))]
   [("kind") (λ (r)
               (article "all posts"
                        (posts 25)))]
   [("few") (λ (r)
               (article "all posts"
                        (posts
                         ; What I imagine you'd like to follow
                         ; articles, notes, photos, etc.
                         #:types (list 1 2 3 4)
                         25)))]
   [("many") (λ (r)
               (article "all posts"
                        (posts
                         ; What I imagine you might want to follow
                         ; + bookmarks, movie watches, etc.
                         #:types (list 1 2 3 4 5 6 7 8 9 10)
                         25)))]))

; Dispatcher for top-level pages like /about and /links
(define (top-level-dispatcher req)
  (let ([path (path/param-path (list-ref (url-path (request-uri req)) 0))])
    (let ([rel-path (string-append "top-level/" (string-trim path ".html") ".txt")])
      (if (file-exists? rel-path)
          (article
           rel-path
           (file->string rel-path))
          (next-dispatcher)))))

; TODO: redirect to "article-title/" if the user requests ".../article-title"
;       this will make sure browsers load relative paths correctly
; TODO: support URL formats from 2019 and before that didn't have months in their paths
; TODO: load in likes, comments. this should be broken out into a post-building function
(define (article-dispatcher req)
  (let ([path-params (filter non-empty-string? (map path/param-path (url-path (request-uri req))))])
    (if (and (equal? (length path-params) 3)
             (regexp-match? #px"^[0-9]{4}$" (first path-params))
             (regexp-match? #px"^[0-9]{2}$" (second path-params))
             (regexp-match? #px"^[a-z]+(\\-[a-z]+)*$" (third path-params)))
        (let ([html-path (apply build-path (append (take path-params 3) (list 'same (string-append (third path-params) ".html"))))])
          (if (file-exists? html-path)
              (article "TITLE" (file->string html-path))
              (next-dispatcher)))
        (next-dispatcher))))

(define stop
  (serve                                                                    ; Dispatcher Order
   #:dispatch (sequencer:make (dispatch/servlet main-dispatcher)            ; 1. primary routes
                              (dispatch/servlet top-level-dispatcher)       ; 2. top-level pages
                              (dispatch/servlet article-dispatcher)         ; 3. article slugs with dates
                                                                            ; TODO: post slugs
                              (files:make #:url->path (make-url->path ".")) ; 4. if path exists, serve it
                              (dispatch/servlet not-found))                 ; 5. 404
   #:listen-ip "127.0.0.1"
   #:port 8000))

(with-handlers ([exn:break? (λ (e)
                              (stop))])
  (sync/enable-break never-evt))