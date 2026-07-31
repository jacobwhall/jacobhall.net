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

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: entries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.entries (
    post_type integer,
    content_location text,
    published boolean DEFAULT true,
    post_title text,
    published_date timestamp without time zone DEFAULT date_trunc('second'::text, timezone('utc'::text, now())),
    updated_date timestamp without time zone,
    display_date text,
    location text,
    display_location boolean DEFAULT false,
    reply_to_id integer,
    reply_to_url text,
    reply_to_author text,
    reply_to_author_h_card text,
    reply_to_author_photo text,
    reply_to_content text,
    author_h_card text,
    author_photo text,
    original_url text,
    content text,
    content_summary text,
    reply_to_title text,
    author text,
    post_id smallint NOT NULL,
    permalink text NOT NULL,
    bookmark_of text,
    whostyle character varying(200)
);


--
-- Name: oldentries; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.oldentries (
    post_type integer NOT NULL,
    post_id integer,
    content_location text,
    published boolean DEFAULT true,
    post_title text,
    published_date timestamp without time zone DEFAULT date_trunc('second'::text, timezone('utc'::text, now())),
    updated_date timestamp without time zone,
    display_date text,
    permalink text NOT NULL,
    location text,
    display_location boolean DEFAULT false,
    reply_to_id integer,
    reply_to_url text,
    reply_to_author text,
    reply_to_author_h_card text,
    reply_to_author_photo text,
    reply_to_content text,
    author_h_card text,
    author_photo text,
    original_url text,
    content text,
    content_summary text,
    reply_to_title text,
    author text,
    new_post_id smallint NOT NULL
);


--
-- Name: entries_new_post_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.entries_new_post_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: entries_new_post_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.entries_new_post_id_seq OWNED BY public.oldentries.new_post_id;


--
-- Name: newentries_post_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.newentries_post_id_seq
    AS smallint
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: newentries_post_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.newentries_post_id_seq OWNED BY public.entries.post_id;


--
-- Name: tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.tags (
    post_id integer NOT NULL,
    tag text NOT NULL
);


--
-- Name: types; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.types (
    type smallint NOT NULL,
    type_name character varying(50) NOT NULL,
    slug character varying(50) NOT NULL
);


--
-- Name: vposts; Type: VIEW; Schema: public; Owner: -
--

CREATE VIEW public.vposts AS
 SELECT types.type,
    types.slug AS type_slug,
    types.type_name,
    entries.post_id,
    entries.author,
    entries.post_title,
    entries.content_location,
    entries.bookmark_of,
    entries.published_date,
    to_char(entries.published_date, 'YYYY-MM-DD"T"HH24:MI:SS"Z"'::text) AS published_date_iso,
    to_char(entries.published_date, 'Month DD, YYYY HH24:MI'::text) AS published_date_disp,
    COALESCE(entries.updated_date, entries.published_date) AS updated_date,
    entries.permalink,
    entries.location,
    COALESCE(entries.display_location, false) AS display_location,
    COALESCE(entries.author_h_card, 'https://jacobhall.net'::text) AS author_h_card,
    entries.author_photo,
    COALESCE(entries.whostyle, 'jacobhall-net'::character varying) AS whostyle,
    entries.original_url,
    entries.reply_to_author,
    entries.reply_to_id,
    entries.reply_to_author_h_card,
    entries.reply_to_author_photo,
    entries.reply_to_title,
    entries.reply_to_content,
    entries.reply_to_url,
    entries.content,
    entries.content_summary
   FROM public.entries,
    public.types
  WHERE ((entries.post_type = types.type) AND (entries.published = true))
  ORDER BY entries.published_date DESC;


--
-- Name: wm_log; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.wm_log (
    source character varying(1000),
    target character varying(1000),
    time_sent timestamp without time zone DEFAULT CURRENT_TIMESTAMP,
    whostyle character varying,
    source_mf jsonb
);


--
-- Name: entries post_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entries ALTER COLUMN post_id SET DEFAULT nextval('public.newentries_post_id_seq'::regclass);


--
-- Name: entries permalink; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.entries ALTER COLUMN permalink SET DEFAULT concat('https://jacobhall.net/', date_part('year'::text, timezone('utc'::text, now())), '/', lpad((date_part('month'::text, timezone('utc'::text, now())))::text, 2, '0'::text), '/', lpad((date_part('day'::text, timezone('utc'::text, now())))::text, 2, '0'::text), '/', lpad((currval('public.newentries_post_id_seq'::regclass))::text, 6, '0'::text));


--
-- Name: oldentries new_post_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oldentries ALTER COLUMN new_post_id SET DEFAULT nextval('public.entries_new_post_id_seq'::regclass);


--
-- Name: oldentries entries_content_location_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oldentries
    ADD CONSTRAINT entries_content_location_key UNIQUE (content_location);


--
-- Name: oldentries entries_post_id_key; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.oldentries
    ADD CONSTRAINT entries_post_id_key UNIQUE (post_id);


--
-- Name: types types_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.types
    ADD CONSTRAINT types_pkey PRIMARY KEY (type);


--
-- PostgreSQL database dump complete
--

