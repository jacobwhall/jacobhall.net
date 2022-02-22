#lang racket

; Thanks to Bogdan Popa for their helpful guide to racket's web server
; https://defn.io/2020/02/12/racket-web-server-guide/

(require db
         net/url
         web-server/servlet-env
         web-server/http
         web-server/templates
         web-server/web-server
         web-server/servlet-dispatch
         web-server/dispatch
         web-server/dispatchers/filesystem-map
         (prefix-in sequencer: web-server/dispatchers/dispatch-sequencer)
         (prefix-in files: web-server/dispatchers/dispatch-files)
         "../pass.rkt")

(define (http-response content)
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

(define (ass->post post-data)
  (include-template "post.txt"))

(define (rows-result->posts result)
  (foldr string-append ""
         (map (λ (r)
                (ass->post (row->ass (rows-result-headers result)
                                     r)))
              (rows-result-rows result))))

(define posts
  (λ (limit
      #:author [author "Jacob Hall"]
      #:types [types (list 1 2 3 4 5 6 7 8 9 10 11 12)])
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

(define (homepage req)
  (http-response (include-template "index.html")))

(define (article title content)
  (http-response (include-template "article.html")))

(define (not-found req)
  (article "404" "<h1>Not Found</h1>")) ; TODO: use xexpr

(define-values (main-dispatcher _)
  (dispatch-rules
   [("") homepage]
   [("kind") (λ (r)
               (article "all posts"
                        (posts 25)))]
   [("about") (λ (r)
                (article "about"
                         (file->string "top-level/about.txt")))]
   [("links") (λ (r)
                (article "links"
                         (file->string "top-level/links.txt")))]
   [("links.html") (λ (r) ; TODO: match all top-level pages, .html or not
                (article "links"
                         (file->string "top-level/links.txt")))]
   [("dreams") (λ (r)
                 (article "dreams"
                          (file->string "top-level/dreams.txt")))]
   [("now") (λ (r)
                 (article "now"
                          (file->string "top-level/now.txt")))]
   [("software") (λ (r)
                 (article "software i use"
                          (file->string "top-level/software.txt")))]
   [("webdev") (λ (r)
                 (article "webdev"
                          (file->string "top-level/webdev.txt")))]
   [("webdev.html") (λ (r)
                 (article "webdev"
                          (file->string "top-level/software.txt")))]))

(define stop
  (serve
   #:dispatch (sequencer:make (dispatch/servlet main-dispatcher)            ; primary routes
                              (files:make #:url->path (make-url->path ".")) ; if path exists, serve it
                              (dispatch/servlet not-found))                 ; 404
   #:listen-ip "127.0.0.1"
   #:port 8000))

(with-handlers ([exn:break? (λ (e)
                              (stop))])
  (sync/enable-break never-evt))