#lang racket

(require db
         web-server/servlet
         web-server/servlet-env
         web-server/templates)

(define pgc
  (postgresql-connect #:user "jhsite"
                      #:database "jacobhall.net"
                      #:password "TS^4jAgNo6#NBW"))


(define (my-app req)
  (define posts "This is where the posts will go")
  (http-response (include-template "index.html")))


(define (http-response content)
  (response/full
    200                  ; HTTP response code.
    #"OK"                ; HTTP response message.
    (current-seconds)    ; Timestamp.
    TEXT/HTML-MIME-TYPE  ; MIME type for content.
    '()                  ; Additional HTTP headers.
    (list                ; Content (in bytes) to send to the browser.
      (string->bytes/utf-8 content))))

;(serve/servlet
; my-app
; #:servlet-path "/"
; #:extra-files-paths
; (list
;  (build-path "/home/jacob/Documents/jacobhall.net")))

(define qseq (query pgc "SELECT post_type, post_title FROM entries"))
qseq