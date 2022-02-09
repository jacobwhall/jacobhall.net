#lang racket

(require db
         web-server/dispatch
         web-server/servlet
         web-server/servlet-env
         web-server/templates
         "../pass.rkt")

(define (a key post)
  (cdr (assoc key post)))

(define (row->ass headers row)
  (map (lambda (h r) make-hash (cons (cdr (first h)) (if (string? r) r ""))) headers (sequence->list row)))

(define (ass->post post-data)
  (include-template "post.html"))

(define (rows-result->posts result)
  (foldl string-append ""
         (map (lambda (r) (ass->post (row->ass (rows-result-headers result) r))) (rows-result-rows result))))

(define posts (rows-result->posts (query pgc "SELECT *
		FROM vPosts
		WHERE type != 5
		AND type != 6
		AND type != 11
		AND author = 'Jacob Hall'
                LIMIT 5")))

(define (homepage req)
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

(serve/servlet;
 homepage
 #:servlet-path "/"
 #:extra-files-paths
 (list
  (build-path "/home/jacob/Documents/jacobhall.net")))
