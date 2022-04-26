#lang racket

; Thanks to Bogdan Popa for their helpful guide to racket's web server
; https://defn.io/2020/02/12/racket-web-server-guide/

(require db
         net/url
         web-server/servlet-env
         web-server/http
         web-server/http/redirect
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

(define post-query
  ; Required argument limit sets upper limit of returned posts
  (λ (limit
      ; Optional argument only returns specific author
      ; TODO: use Microformats standard author UID rather than name
      #:author [author "*"] ; TODO: * is invalid
      #:types [types (list 1 2 3 4 5 6 7 8 9 10 11 12)])
    (query pgc
           (prepare pgc "SELECT *
                         FROM vPosts
                         WHERE author = $2
                         AND type=ANY($3::int[])
                         LIMIT $1")
           limit
           author
           types)))

; This is a wrapper function for post-query above
(define posts
  (λ (limit
      #:author [author "Jacob Hall"]
      #:types [types (list 1 2 3 4 5 6 7 8 9 10 11 12)])
    ; Convert the result of PostgreSQL view "vPosts" into embeddable HTML
    (rows-result->posts (post-query limit
                                    #:author author))))

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

(define (dir-redirect-dispatcher req)
  (let ([req-path (path->string (simplify-path (url->path (request-uri req))))]
        [dir-path (path->string (simplify-path (path->directory-path (url->path (request-uri req)))))])
    (if (equal? req-path dir-path)
        (next-dispatcher)
        (redirect-to dir-path permanently))))

(define (title-slug? slug)
  (regexp-match? #px"^[a-z]+(\\-[a-z]+)*$" slug))

(define (valid-dir-dispatcher req)
  ; Make path relative, remove trailing /
  ; TODO: cleaner way to do this?
  (let ([req-path (string-trim (path->string (url->path (request-uri req)))
                               "/")])
    ; Does path refer to a real directory on the filesystem?
    (if (directory-exists? req-path)
        (let ([index-path (build-path req-path "index.html")])
          (if (file-exists? index-path)
              ; Path/index.html exists, serve that
              (file->string index-path)
              ; Path /index.html does not exist, do we have valid article files?
              (let ([article-path (build-path req-path "article.txt")]
                    [title-path (build-path req-path "title.txt")])
                (if (and (file-exists? article-path)
                         (file-exists? title-path))
                    ; Article files exist, build article
                    ; TODO: inject title into page, load in likes / comments.
                    (article (file->string title-path)
                             (file->string article-path))
                    (next-dispatcher)))))
        (let ([path-elements (explode-path (string->path req-path))])
          ; Does the path slug match that of a post?
          (if (and (regexp-match? #px"^[0-9]{4}$" (first path-elements))
                   (regexp-match? #px"^[0-9]{2}$" (second path-elements))
                   (regexp-match? #px"^[0-9]{2}$" (third path-elements))
                   (regexp-match? #px"^[0-9]{6}$" (fourth path-elements)))
              ; Path slug does match, build post!
              (print "this is a post that should be built")
              ; Path slug does not match, next-dispatcher
              (next-dispatcher))))))

(define stop
  (serve                                                                    ; Dispatcher Order
   #:dispatch (sequencer:make (dispatch/servlet main-dispatcher)            ; 1. primary routes
                              (dispatch/servlet top-level-dispatcher)       ; 2. top-level pages
                              (files:make #:url->path (make-url->path ".")) ; 3. if path exists, serve it
                              (dispatch/servlet dir-redirect-dispatcher)    ; 4. redirect /abc to /abc/
                              (dispatch/servlet valid-dir-dispatcher)       ; 5. dirs with indexes or articles
                              ; (dispatch/servlet slug-dispatcher)          ; 6. post slugs
                              (dispatch/servlet not-found))                 ; 7. 404
   #:listen-ip "127.0.0.1"
   #:port 8000))

(with-handlers ([exn:break? (λ (e)
                              (stop))])
  (sync/enable-break never-evt))