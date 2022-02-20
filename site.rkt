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
  (map (λ (h r) make-hash (cons (cdr (first h)) (if (sql-null? r) "" r))) headers (sequence->list row)))

(define (ass->post post-data)
  (include-template "post.html"))

(define (rows-result->posts result)
  (foldr string-append ""
         (map (λ (r) (ass->post (row->ass (rows-result-headers result) r))) (rows-result-rows result))))

(define posts (rows-result->posts (query pgc "SELECT *
		FROM vPosts
		WHERE type != 5
		AND type != 6
		AND type != 11
		AND author = 'Jacob Hall'
                LIMIT 5")))

(define (homepage req)
  (http-response (include-template "index.html")))

(define (article content)
  (http-response (include-template "article.html")))

(define (links req)
  (http-response (port->string (open-input-file "links.html"))))

(define (not-found req)
  (article "<h1>Not Found</h1>")) ; TODO: use xexpr

(define-values (main-dispatcher _)
  (dispatch-rules
   [("") homepage]
   [("kind") (λ (r) (article posts))]
   [("links") (λ (r) (article (file->string "links.html")))]
   [("dreams") (λ (r) (article (file->string "dreams.html")))]))

(define stop
  (serve
   #:dispatch (sequencer:make (dispatch/servlet main-dispatcher)
                              (files:make #:url->path (make-url->path "."))
                              (dispatch/servlet not-found))
   #:listen-ip "127.0.0.1"
   #:port 8000))

(with-handlers ([exn:break? (λ (e)
                              (stop))])
  (sync/enable-break never-evt))