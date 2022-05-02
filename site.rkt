#lang racket

; Thanks to Matthew Butterick for his book Beautiful Racket
; https://beautifulracket.com/
;
; and to Bogdan Popa for their helpful guide to racket's web server
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

(define (server-error message)
  (response/full
   500
   #"Internal Server Error"
   (current-seconds)
   #"text/plain; charset=utf-8"
   '()
   (list
    (string->bytes/utf-8 message))))

(define (bad-request message)
  (response/full
   400
   #"Bad Request"
   (current-seconds)
   #"text/plain; charset=utf-8"
   '()
   (list
    (string->bytes/utf-8 message))))

(define (insert-webmention source target)
  (query pgc
         (prepare pgc "INSERT INTO wm_log
                       (source, target)
                       VALUES
                       ($1, $2)")
         source
         target))

(define (process-webmention req)
  (let ([source (bindings-assq (string->bytes/utf-8 "source") (request-bindings/raw req))]
        [target (bindings-assq (string->bytes/utf-8 "target") (request-bindings/raw req))])
    (if (and source target)
        (let ([target-url (string->url (bytes->string/utf-8 (binding:form-value target)))]
              [source-url (string->url (bytes->string/utf-8 (binding:form-value source)))]
              [schemes (list "http" "https")])
          (if (and (member (url-scheme source-url) schemes)
                   (member (url-scheme target-url) schemes))
              (if (equal? (url-host target-url) "jacobhall.net")
                  (if (simple-result? (insert-webmention (url->string source-url) ; TODO: more thoroughly validate insertion?
                                                         (url->string target-url)))
                      (response/full
                       202
                       #"Accepted"
                       (current-seconds)
                       #"text/plain; charset=utf-8"
                       '()
                       (list
                        (string->bytes/utf-8 "Thanks for the webmention! I have queued it for processing.")))
                      (server-error "An unknown error occured when inserting your webmention into my database."))
                  (bad-request "Target URL must point to jacobhall.net"))
              (bad-request "Source and target URLs must have either HTTP or HTTPS schemes.")))
        (bad-request "Webmention request must include both source and target!"))))

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
; e.g. from function "post-from-id" or "posts-query"
(define (rows-result->posts result)
  (foldr string-append ""
         (map (λ (r)
                (ass->post (row->ass (rows-result-headers result)
                                     r)))
              (rows-result-rows result))))

(define (post-from-id post-id)
  (query pgc
         "SELECT *
            FROM vPosts
            WHERE post_id = $1
            LIMIT 1"
         post-id))

(define posts-query
  ; Required argument limit sets upper limit of returned posts
  (λ (limit
      ; Optional argument only returns specific author
      ; TODO: use Microformats standard author UID rather than name
      #:author [author "*"] ; TODO: * is invalid
      #:post-id [post-id "*"] ; ""
      #:types [types (list 1 2 3 4 5 6 7 8 9 10 11 12)])
    (query pgc
           "SELECT *
            FROM vPosts
            WHERE author = $1
            AND type=ANY($2::int[])
            LIMIT $3"
           author
           types
           limit)))

(define (replies-query reply-to-id)
  (query pgc
         "SELECT *
          FROM vPosts
          WHERE type = 7
          AND reply_to_id = $1
          ORDER BY published_date ASC"
         reply-to-id))

(define (post-date-id-query year month day post-id)
  (query pgc
         "SELECT *
          FROM vPosts
          WHERE post_id = $1
          AND date_part('year', published_date) = $2
          AND date_part('month', published_date) = $3
          AND date_part('day', published_date) = $4
          LIMIT 1"
         post-id
         year
         month
         day))

(define (post-permalink-query permalink)
  (query pgc
         "SELECT *
          FROM vPosts
          WHERE permalink = $1
          LIMIT 1"
         permalink))

; This is a wrapper function for posts-query above
(define posts
  (λ (limit
      #:author [author "Jacob Hall"]
      #:types [types (list 1 2 3 4 5 6 7 8 9 10 11 12)])
    ; Convert the result of PostgreSQL view "vPosts" into embeddable HTML
    (rows-result->posts (posts-query limit
                                     #:author author))))

(define (build-comments post-id)
  (let ([this-post-result (replies-query post-id)])
    (foldr string-append ""
           (map (λ (r)
                  (let ([this-ass (row->ass (rows-result-headers
                                             this-post-result)
                                            r)])
                    (string-append (ass->post this-ass)
                                   (build-comments (a "post_id" this-ass)))))
                (rows-result-rows this-post-result)))
    ))

; The homepage gets its own template
(define (homepage req)
  (http-200 (include-template "index.txt")))

; Articles get their own template
; TODO: optionally disable likes/comments
(define article
  (λ (title
      content
      #:article-tags [article-tags #t]
      #:insert-title [insert-title #f]
      #:post-id [post-id #f]) ; TODO: load in comments, likes
    (http-200 (include-template "article.txt"))))

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
                       (posts 25)
                       #:article-tags #f))]
   [("kind") (λ (r)
               (article "all posts"
                        (posts 25)
                        #:article-tags #f))]
   [("few") (λ (r)
              (article "all posts"
                       (posts
                        ; What I imagine you'd like to follow
                        ; articles, notes, photos, etc.
                        #:types (list 1 2 3 4)
                        25)
                       #:article-tags #f))]
   [("many") (λ (r)
               (article "all posts"
                        (posts
                         ; What I imagine you might want to follow
                         ; + bookmarks, movie watches, etc.
                         #:types (list 1 2 3 4 5 6 7 8 9 10)
                         25)
                        #:article-tags #f))]
   [("webmention") #:method "post" process-webmention]))

; Dispatcher for top-level pages like /about and /links
(define (top-level-dispatcher req)
  (let ([path (string-trim (path/param-path (list-ref (url-path (request-uri req)) 0)) ".html")])
    (let ([rel-path (string-append "top-level/" path ".txt")])
      (if (file-exists? rel-path)
          (article
           path
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
                    ; TODO: load in likes / comments.
                    (article (file->string title-path)
                             (file->string article-path)
                             #:insert-title #t
                             ; If this article is in the database, let's pass along the post id
                             #:post-id (let ([article-results (post-permalink-query
                                                               (string-trim (string-append "https://jacobhall.net/" req-path)
                                                                            "."))])
                                         (if (empty? (rows-result-rows article-results))
                                             #f
                                             (a "post_id" (row->ass (rows-result-headers article-results)
                                                                    (first (rows-result-rows article-results)))))))
                    (next-dispatcher)))))
        (let ([path-elements (explode-path (string->path req-path))])
          ; Does the path slug match that of a post?
          (if (and (regexp-match? #px"^[0-9]{4}$" (first path-elements))
                   (regexp-match? #px"^[0-9]{2}$" (second path-elements))
                   (regexp-match? #px"^[0-9]{2}$" (third path-elements))
                   (regexp-match? #px"^[0-9]{6}$" (fourth path-elements)))
              ; Path slug does match, build post!
              (let ([post-result (post-date-id-query (string->number (path->string (first path-elements)))
                                                     (string->number (path->string (second path-elements)))
                                                     (string->number (path->string (third path-elements)))
                                                     (string->number (path->string (fourth path-elements))))])
                (article "post"
                         (ass->post (row->ass (rows-result-headers post-result)
                                              (first (rows-result-rows post-result))))
                         #:article-tags #f
                         #:post-id (string->number (path->string (fourth path-elements)))))
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