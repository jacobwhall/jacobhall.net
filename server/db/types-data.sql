--
-- PostgreSQL database dump
--

-- Dumped from database version 13.7
-- Dumped by pg_dump version 13.7

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: types; Type: TABLE DATA; Schema: public; Owner: postgres
--

INSERT INTO public.types VALUES (1, '🖋️ ARTICLE', 'article');
INSERT INTO public.types VALUES (2, '📝 NOTE', 'note');
INSERT INTO public.types VALUES (3, '📷 PHOTO', 'photo');
INSERT INTO public.types VALUES (4, '🎥 VIDEO', 'video');
INSERT INTO public.types VALUES (6, '❤️ LIKE', 'like');
INSERT INTO public.types VALUES (7, '↩️ REPLY', 'reply');
INSERT INTO public.types VALUES (5, '🔖 BOOKMARK', 'bookmark');
INSERT INTO public.types VALUES (8, '🔄 REPOST', 'repost');
INSERT INTO public.types VALUES (9, '✉️ RSVP', 'rsvp');
INSERT INTO public.types VALUES (10, '📎 FILE', 'file');
INSERT INTO public.types VALUES (11, '📺 WATCHED', 'watch');


--
-- PostgreSQL database dump complete
--

