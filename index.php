<!DOCTYPE html>
<html lang="en">
<head>
	<title>Jacob Hall</title>
	<meta charset="utf-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="theme-color" content="#FFFFFF"/>
	<meta name="Description" content="Jacob Hall's personal website">
	<style>
		body {
			text-align: center;
			font-family: 'Work Sans', sans-serif;
			font-size: 100%;
			font-size: max(100%, .4cm);
		}

		@media (max-aspect-ratio: 1/1) {
			body {
				font-size: 4vw;
				font-size: min(4vw, .4cm);
			}
		}
		body, h1, h2, h3, h4, h5, h6 {
			font-size-adjust: 0.5;
		}

		ul {
			list-style-position: inside;
			padding-left: 0.5em;
		}

		#announcement {
			position: absolute;
			top: 0;
			left: 0;
			width: 100%;
			background-color: red;
			color: white;
			line-height: 1.5em;
		}

		#announcement a {
			color: white;
		}

		#firstbox {
			text-align: center;
			padding-top: 2em;
			width: 100%;
		}

		.profilepic {
			border-radius:50%;
		}

		#titleprofilepic {
			height: 10em;
			max-height: 50vh;
		}

		#bottom h1 {
			font-family: 'Montserrat', sans-serif;
			font-size: 2em;
			line-height: 0.5em;
			font-weight: bold;
		}

		#bottom #socials a {
			font-size: 1.5em;
			text-decoration: none;
		}

		#contentbox {
			display: inline-block;
			width: 100%;
			max-width: 50em;
			min-width: 10%
		}

		article {
			text-align: left;
			padding-bottom: 1.5em;
		}
		
		article img {
			width: 100%;
		}

		#subsequentbox {
			text-align: center;
			width: 100%;
			display: flex;
			flex-flow: row wrap;
			justify-content: space-between;
		}

		.list {
			padding: 1em;
		}

		.list ul {
			text-align: left;
		}

		#blog {
			text-align: justify;
		}

		.centeredtext {
			text-align: center;
		}

		.video {
			width: 100%;
			max-width: 50em;
			min-width: 10%
		}
		.code {
			overflow-x:auto;
			/* white-space: pre-wrap; */
			font-family: monospace;
			color: #c5cdd9;
			background-color: #2c2e34;
			border: 1em solid #2c2e34;
			border-radius: 1em;	
		}
		.code a {
			color: inherit;
		}
		.code .Comment {
			color: #828a98;
			font-style: italic;
		}
		.code .Constant {
			color: #a0c980;
		}
		/* not used? might remove. i don't make errors ;) */
		.code .Error {
			color: #ffffff;
			background-color: #ff6060;
			padding-bottom: 1px;
		}
		.code .Identifier {
			color: #6cb6eb;
		}
		.code .PreProc {
			color: #ec7279;
		}
		.code .Special {
			color: #c5cdd9;
		}
		.code .Statement {
			color: #d38aea;
		}	
		.code .vimCommand {
			color: #d38aea;
		}	
		#mvflexbox {
			display: flex;
			flex-wrap: wrap;
			justify-content: space-between;
		}

		.mvthumb {
			width:24em;
			text-align: center;
		}

		.mvthumb img {
			width:100%;
		}
		#license {
			display: inline-block;
			width: 50em;
			min-width: 50%;
			max-width: 100%;
		}
	</style>
</head>
<body>
<!-- <div id="announcement">Looking for a resource I sent you here for? <a href="/">Click here</a></div> -->
<div class="h-card" id="firstbox">
	<a class="u-url" href="https://jacobhall.net"></a>
	<div id="top">
		<img class="profilepic u-photo" id="titleprofilepic" src="images/toothbrush_profile_small.jpg" alt=""/>
	</div>
	<div id="bottom">
		<h1 class="p-name">Jacob Hall</h1>
		<p><i>howdy! this is my weblog.<br>please leave your shoes by the door</i></p>
		<p id="socials">
			<a class="u-email" title="email me" href="mailto:totallyuneekemail@gmail.com">📧</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Instagram profile" href="https://www.instagram.com/jacobwhall/">📷</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Keybase profile" href="https://keybase.io/totallyuneekname">🔑</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Couchsurfing profile" href="https://www.couchsurfing.com/people/jacob-hall-10">🛋️</a>
			&nbsp;&nbsp;
			<a rel="me" title="my Mastodon profile" href="https://fosstodon.org/@jacobhall">🐘</a>
		</p>
		<p><a href="about.html">about</a> - <a href="links.html">linkroll</a> - <a href="dreams.html">ideas</a></p>
	</div>
</div>
<p>
<div id="contentbox">
<!--
<p>
ok so the current concept is free-form, undated articles each preceded by a relevant emoji
</p>
-->
<article>
<h2>⌨️ Simple HTML code highlighting workflow with vim</h2>
<p>
Lately I've wanted to post code snippets to my website, but I want them to look pretty and highlighted instead of just using boring old &lt;pre&gt; tags.
I know a lot of people use fancy scripts to highlight text client-side, but one of the rules I've set for myself in building this website is to avoid Javascript.
Besides, is all that effort and computation really needed for some colored text?
So I decided to find a workflow in which I can export my code into pre-formatted HTML.
</p>
<p>
I recently put some effort into customizing vim, and after trying a bunch of themes settled on <a href="https://github.com/sainnhe/edge">Edge</a>.
Staring at my beautifully-highlighted code in vim, I wondered if I could just replicate the same effect on my website.
I quickly found the vim <code>TOhtml</code> function, which does exactly that!
All you have to do is run <code>TOhtml</code> in vim, and it outputs a formatted HTML file complete with CSS rules that color different types of syntax exactly how it's displayed in the editor. Hoorah!
</p>
<p>
After messing with <code>TOhtml</code> for a while, I realized that it was not actually outputting the same syntax highlighting that I was seeing in my terminal.
For example, functions appeared light blue to me in vim, but the exported HTML did not discern them at all.
It took me a long time to realize that the issue was with my color scheme, Edge!
I have no idea why, but <code>TOhtml</code> does not play well with Edge and ignores some of its syntax highlighting rules.
It also seems to forget that I have true colors turned on!
I tried to run <code>TOhtml</code> using the popular <a href="https://github.com/altercation/solarized">Solarized</a> scheme, and it worked without hitch!
</i>
<p>
...but I want to use the Edge color scheme.
And besides, if I ever want to switch color schemes in the future, I don't want to worry about their compatibility with my current syntax highlighting workflow.
In my .vimrc I've created a variable that defines my current color scheme of choice:
</p>
<pre class="code">
<span class="Comment">&quot; Use edge color scheme: <a href="https://github.com/sainnhe/edge">https://github.com/sainnhe/edge</a></span>
let <span class="Identifier">g:usual_colorscheme</span> <span class="Statement">=</span> <span class="Constant">&quot;edge</span><span class="Constant">&quot;</span>

<span class="Comment">&quot; Set our colorscheme to the one I've defined above</span>
<span class="vimCommand">execute</span> <span class="Constant">&quot;colorscheme </span><span class="Constant">&quot;</span><span class="Statement">.</span><span class="Identifier">g:usual_colorscheme</span>
</pre>
<p>
By doing that, I can declare a function that temporarily sets my color scheme to solarized when I want to use TOhtml, and then define a command to run it:
</p>
<pre class="code">
<span class="Comment">&quot; Function to output better syntax-highlighted HTML using solarized</span>
<span class="vimCommand">function</span> SolarizedTOhtml<span class="Special">()</span>
	<span class="vimCommand">colorscheme</span> <span class="vimIsCommand">solarized</span>
	<span class="vimIsCommand">TOhtml</span>
	<span class="vimCommand">execute</span> <span class="Constant">&quot;colorscheme </span><span class="Constant">&quot;</span><span class="Statement">.</span><span class="Identifier">g:usual_colorscheme</span>
<span class="vimCommand">endfunction</span>

<span class="vimCommand">command</span> <span class="vimIsCommand">NEWhtml</span> <span class="Identifier">call</span> <span class="Identifier">SolarizedTOhtml</span>()
</pre>
<p>
It was humorously difficult to define suitable CSS rules for these vimscript snippets, but I think they look very nice considering how simple this system is.
Since I'm only exporting code using Solarized now, the same CSS rules should work on future code snippets without much modification, and I'll be able to adjust them all uniformly with ease.
The only other adjustments I've made to this setup are the following two settings in my .vimrc:
</p>
<pre class="code">
<span class="Comment">&quot; For :TOhtml output, remove line numbers</span>
 let <span class="Identifier">g:html_number_lines</span> <span class="Statement">=</span> <span class="Constant">0</span>

<span class="Comment">&quot; For :TOhtml output, ignore code folding</span>
 let <span class="Identifier">g:html_ignore_folding</span> <span class="Statement">=</span> <span class="Constant">1</span>
</pre>
<p>
Have a beautiful day!
</p>
</article>
<article>
<h2>📍 Mapping Education Development Projects in Afghanistan</h2>
<p>
This is the first time I've used R and LaTeX together, using a package called knitr. I believe papers that include statistical analyses should be fully reproducable, and part of my objective for this project is to learn how to accomplish that. I was partly inspired by <a href="https://blogs.worldbank.org/opendata/where-does-chinese-development-finance-go">this article</a> on Chinese investments (also using AidData data), available as an R notebook <a href="http://tariqkhokhar.com/data/aiddata-china-blog.html">here</a>. I also used <a href="https://eriqande.github.io/rep-res-eeb-2017/map-making-in-R.html">this guide</a> to help navigate the weird world of the maps R package. As I got further into the weeds of plotting the population density data, <a href="https://datacarpentry.org/r-raster-vector-geospatial/01-raster-structure/">this Data Carpentry lesson</a> was indispensable.
</p>
<pre class="code">
<span class="PreProc">library</span><span class="Special">(</span>tidyverse<span class="Special">)</span>
<span class="PreProc">library</span><span class="Special">(</span>maps<span class="Special">)</span>
<span class="PreProc">library</span><span class="Special">(</span>mapdata<span class="Special">)</span>
<span class="PreProc">library</span><span class="Special">(</span>raster<span class="Special">)</span>
<span class="PreProc">library</span><span class="Special">(</span>rasterVis<span class="Special">)</span>
<span class="PreProc">library</span><span class="Special">(</span>rgdal<span class="Special">)</span>
<span class="PreProc">library</span><span class="Special">(</span>viridis<span class="Special">)</span>
</pre>
<p>
I requested a bunch of data about Afghanistan's education and forestry development from AidData. The download link for my query is <a href="http://geo.aiddata.org/data/geoquery_results/5e65e054c15e0068ea94abc5/5e65e054c15e0068ea94abc5.zip">here</a>. I decided to focus on the education data for this project, and experiment with overlaying the education project locations over a population density map of Afghanistan. I hope for this map to effectively convey how well-distributed these projects have been relative to populous areas. Have a majority of these projects been in cities, or in a specific region?
</p>
<p>
Directly converting the population density raster to a data frame ate my laptop's memory and caused R to crash, simply outputting "Killed" in my terminal. I ended up <a href="https://stackoverflow.com/q/61535383/2180108">asking for help</a>, and it was suggested that I aggregate the data before conversion. I'm annoyed that R can't handle this data file with better memory management (and I dream of pixel-perfect output!), but the aggregated data still looks very nice.
</p>
<pre class="code">
<span class="Comment"># Import AidData dataset</span>
<span class="Comment"># Make sure you edit this path to reflect the file's location on your machine</span>
inscription_data <span class="Statement">=</span> <span class="Identifier">read.csv</span><span class="Special">(</span><span class="Constant">&quot;results.csv&quot;</span><span class="Special">,</span> header <span class="Statement">=</span> <span class="Constant">TRUE</span><span class="Special">)</span>
education_data <span class="Statement">&lt;-</span> <span class="Identifier">filter</span><span class="Special">(</span>inscription_data<span class="Special">,</span>
                         <span class="Identifier">grepl</span><span class="Special">(</span><span class="Constant">&quot;Afghanistan&quot;</span><span class="Special">,</span> recipients<span class="Special">)</span><span class="Special">,</span>
                         <span class="Identifier">grepl</span><span class="Special">(</span><span class="Constant">&quot;education&quot;</span><span class="Special">,</span> ad_sector_names<span class="Special">))</span>

<span class="Comment"># Import population density data for Afghanistan (2020)</span>
afg_pop <span class="Statement">&lt;-</span> <span class="Identifier">raster</span><span class="Special">(</span><span class="Constant">&quot;afg_ppp_2020.tif&quot;</span><span class="Special">)</span>
<span class="Comment"># Theoretically not necessary if you have a lot of RAM overhead</span> 🙄
afg_pop2 <span class="Statement">&lt;-</span> <span class="Identifier">aggregate</span><span class="Special">(</span>afg_pop<span class="Special">,</span> <span class="Constant">10</span><span class="Special">)</span>
<span class="Comment"># This is the line that caused my laptop to crash without the aggregated input</span>
afg_pop_df <span class="Statement">&lt;-</span> <span class="Identifier">as.data.frame</span><span class="Special">(</span>afg_pop2<span class="Special">,</span> xy <span class="Statement">=</span> <span class="Constant">TRUE</span><span class="Special">)</span>

afg <span class="Statement">&lt;-</span> <span class="Identifier">map_data</span><span class="Special">(</span><span class="Constant">&quot;worldHires&quot;</span><span class="Special">,</span> <span class="Constant">&quot;Afghanistan&quot;</span><span class="Special">)</span>

<span class="Comment"># Let's define a map that plots the population data all pretty</span>
pop_map <span class="Statement">&lt;-</span> <span class="Identifier">ggplot</span><span class="Special">()</span> <span class="Statement">+</span>
  <span class="Comment"># Remove axis labels because it is clear they are lat/long</span>
  <span class="Identifier">theme</span><span class="Special">(</span>axis.title.x <span class="Statement">=</span> <span class="Identifier">element_blank</span><span class="Special">()</span><span class="Special">,</span>
        axis.title.y <span class="Statement">=</span> <span class="Identifier">element_blank</span><span class="Special">())</span> <span class="Statement">+</span>
  <span class="Comment"># Add population data</span>
  <span class="Identifier">geom_raster</span><span class="Special">(</span>data <span class="Statement">=</span> afg_pop_df<span class="Special">,</span>
              <span class="Identifier">aes</span><span class="Special">(</span>x <span class="Statement">=</span> x<span class="Special">,</span> y <span class="Statement">=</span> y<span class="Special">,</span> fill <span class="Statement">=</span> afg_ppp_2020<span class="Special">))</span> <span class="Statement">+</span>
  <span class="Comment"># Define scaling for population data</span>
  <span class="Identifier">scale_fill_continuous</span><span class="Special">(</span>name <span class="Statement">=</span> <span class="Constant">&quot;Population</span><span class="Special">\n</span><span class="Constant">Density&quot;</span><span class="Special">,</span>
              <span class="Comment"># Poke holes wherever there isn't data</span>
              na.value <span class="Statement">=</span> <span class="Constant">NA</span><span class="Special">,</span>
              <span class="Comment"># Use pretty, colorblind-friendly colours</span>
              type <span class="Statement">=</span> <span class="Constant">&quot;viridis&quot;</span><span class="Special">,</span>
              <span class="Comment"># Use logarithmic scale</span>
              trans <span class="Statement">=</span> <span class="Constant">&quot;log10&quot;</span><span class="Special">,</span>
              <span class="Comment"># Custom breaks to make key more readable</span>
              breaks <span class="Statement">=</span> <span class="Identifier">c</span><span class="Special">(</span><span class="Constant">0.1</span><span class="Special">,</span><span class="Constant">1</span><span class="Special">,</span><span class="Constant">5</span><span class="Special">,</span><span class="Constant">20</span><span class="Special">,</span><span class="Constant">100</span><span class="Special">))</span> <span class="Statement">+</span>
  <span class="Comment"># Add outline of Afghanistan on top for a cleaner edge</span>
  <span class="Identifier">geom_polygon</span><span class="Special">(</span>data <span class="Statement">=</span> afg<span class="Special">,</span>
               <span class="Identifier">aes</span><span class="Special">(</span>x <span class="Statement">=</span> long<span class="Special">,</span> y <span class="Statement">=</span> lat<span class="Special">,</span> group <span class="Statement">=</span> group<span class="Special">)</span><span class="Special">,</span>
               fill <span class="Statement">=</span> <span class="Constant">NA</span><span class="Special">,</span>
               color <span class="Statement">=</span> <span class="Constant">&quot;black&quot;</span><span class="Special">)</span>

<span class="Comment"># Now let's add the education project data to the map, and plot it!</span>
pop_map <span class="Statement">+</span>
  <span class="Comment"># Add education project sites</span>
	<span class="Identifier">geom_point</span><span class="Special">(</span>data <span class="Statement">=</span> education_data<span class="Special">,</span>
             <span class="Identifier">aes</span><span class="Special">(</span>x <span class="Statement">=</span> longitude<span class="Special">,</span> y <span class="Statement">=</span> latitude<span class="Special">)</span><span class="Special">,</span>
             <span class="Comment"># Circular points</span>
             shape <span class="Statement">=</span> <span class="Constant">21</span><span class="Special">,</span>
	           size <span class="Statement">=</span> <span class="Constant">1</span><span class="Special">,</span>
             color <span class="Statement">=</span> <span class="Constant">&quot;white&quot;</span><span class="Special">,</span>
             <span class="Comment"># Do not fill circles</span>
             fill <span class="Statement">=</span> <span class="Constant">NA</span><span class="Special">)</span> <span class="Statement">+</span>
  <span class="Identifier">coord_equal</span><span class="Special">()</span>
</pre>
<img src="/2020/mapping-education-afghanistan/pop_map_1.png" alt="Population heatmap of Afghanistan with projects overlayed as circles">
<p>
That looks great! I know it's wrong to not have units for the population density scale, and that's something I'd like to fix. The value of each pixel in the original image represented the number of people in the area that it covered, so the numbers on this scale should represent the number of people who live within each of the aggregated chunks. I think this issue would be best solved by calculating the distance each chunk covers in mi/km, and then adjusting the values in the legend to represent people per square mi/km.
</p>
<p>
That cluster of projects (and people!) in the Northeast is Kabul. How many projects are occuring in the greater Kabul area?
</p>
<pre class="code">
<span class="Comment"># Coordinates from Wikipedia article on Kabul</span>
kabul_lat <span class="Statement">&lt;-</span> <span class="Constant">34.525278</span>
kabul_long <span class="Statement">&lt;-</span> <span class="Constant">69.178333</span>
radius <span class="Statement">&lt;-</span> <span class="Constant">0.3</span>

<span class="Comment"># New data frame from education_data with just the rows within radius of those coordinates</span>
near_kabul <span class="Statement">&lt;-</span> <span class="Identifier">filter</span><span class="Special">(</span>education_data<span class="Special">,</span>
                     <span class="Identifier">between</span><span class="Special">(</span>latitude<span class="Special">,</span> <span class="Special">(</span>kabul_lat <span class="Statement">-</span> radius<span class="Special">)</span><span class="Special">,</span> <span class="Special">(</span>kabul_lat <span class="Statement">+</span> radius<span class="Special">))</span><span class="Special">,</span>
                     <span class="Identifier">between</span><span class="Special">(</span>longitude<span class="Special">,</span> <span class="Special">(</span>kabul_long <span class="Statement">-</span> radius<span class="Special">)</span><span class="Special">,</span> <span class="Special">(</span>kabul_long <span class="Statement">+</span> radius<span class="Special">)))</span>
<span class="Comment"># The number of projects within that radius can be determined using the following command</span>
<span class="Identifier">nrow</span><span class="Special">(</span>near_kabul<span class="Special">)</span>
<span class="Comment"># I've suppressed the output of this code chunk so that I can call this command inline</span>
</pre>
<img src="/2020/mapping-education-afghanistan/pop_map_2.png" alt="The same population heatmap, with one larger circle around Kabul">
<p>
I think that looks a lot nicer, although it might benefit from more detailed info in the legend about what different sizes of circles indicate. I don't notice anything groundbreaking on this map. There might be fewer projects on the Western side of the country, but I guess that has more to do with the population around Kabul than any other bias. Kabul is nearly 10 times as populous as the second-biggest city, Kandahar, so it is no surprise to see a majority of projects in that region.
</p>
<p>
Is this really all of the projects? Our education_data variable has `r nrow(education_data)` rows, and even with the `r nrow(near_kabul)` removed around Kabul, are all of them being displayed? I noticed that the dataset has rounded numbers for a lot of the latitude and longitude values, which could mean that many projects in the same town are drawn on top of each other. For example, there are three projects in Badakhshan that are all listed as having the exact same coordinates, despite differing descriptions, years, and dollars spent. There are two methods I'd like to use to mitigate this problem. The first is to cluster points that are within a radius of each other, and draw circles of proportional size to represent each cluster. The second is to calculate the average population density within a radius of each project, and then create a bar chart with each bar representing a range of population densities, and its length representing the number of projects that fall within an area of that density. It might be useful to create a few charts using different radii of average density.
</p>
<p>
Other variables could be controlled to better represent the differences in aid allocation across Afghanistan. For example, the size of each point on the map could be scaled based on the monetary value of the project it represents, giving the viewer a better idea of where the majority of funds are allocated geographically. Projects in urban areas might receive more funding to serve more people, or maybe the cost of building a school in a rural area would be significantly more expensive, for example. The source of funding, years active, and preexisting school enrollment are all potentially insightful variables to incorporate in a visualization like this.
</p>
<h3>Data Sources</h3>
<p>
  Goodman, S., BenYishay, A., Lv, Z., & Runfola, D. (2019). GeoQuery: Integrating HPC systems andpublic web-based geospatial data tools. Computers & Geosciences, 122, 103-112.
    The data from AidData used in this project were downloaded from <a href="http://geo.aiddata.org/query/#!/status/5e65e054c15e0068ea94abc5">this page</a>.
</p>
<p>
  WorldPop (www.worldpop.org - School of Geography and Environmental Science, University of Southampton; Department of Geography and Geosciences, University of Louisville; Departement de Geographie, Universite de Namur) and Center for International Earth Science Information Network (CIESIN), Columbia University (2018). Global High Resolution Population Denominators Project - Funded by The Bill and Melinda Gates Foundation (OPP1134076). https://dx.doi.org/10.5258/SOTON/WP00645
    The data from WorldPop used in this project were downloaded from <a href="https://www.worldpop.org/geodata/summary?id=6324">this page</a>.
</p>
</article>
<article>
<h2>✏️ Some thoughts on social media and online art</h2>
<p>
I've been thinking a lot recently about my relationship with technology, and what exactly I want out of my interactions with my phone and computer.
It's becoming clearer to me that despite the unimaginable amount of information, entertainment, and discourse available on the internet, so much of the content I end up consuming every day feels dull and unmemorable.
One of the things that bothers me most about the internet is how most search engines and social networks try their best to serve me content related to content I've already seen.
</p>

<p>
I've heard a lot of arguments against social media recently.
Many people are upset about Facebook or Twitter having too much political power, collecting too much data about their users, or acting in anti-competitive ways.
To be honest, I largely disagree with those arguments.
Unless their government or service provider is censoring content (which I believe is wrong), anyone who accesses the internet has complete control over what sites they choose to visit, what browser they use, etc.
How you interact with the internet is entirely your decision, and websites can do whatever they like with the data you provide them.
So, how will I solve this problem of not enjoying my time spent on social media?
I will delete the accounts on, and more importantly stop visiting, the sites that I do not enjoy visiting.
</p>

<p>
I love the internet, and I love discovering content on the internet, but I want it to be a tool I only use in short bursts of maximum utility.
By that I mean that I want to log in for periods of an hour or less, and spend that time consuming high-quality, entirely novel content.
Given the sheer amount of data uploaded to the web every second, surely that would be possible?
</p>

<p>
A Google search can be unhelpful when approaching a new topic, and it can take a lot of digging to find a few good keywords.
I've noticed many times that my search results look a lot different than my friends', a phenomenon described in Eli Pariser's <a href="https://www.ted.com/talks/eli_pariser_beware_online_filter_bubbles">TED talk on "filter bubbles."</a>
News publications and link aggregators have all but solved this problem for current events and videos of cats.
But I love watching music videos, for example, and those are really hard to discover.
Youtube's recommendation algorithm is great, but there is a sense of randomness to its suggestions, and it's designed to keep me clicking.
This isn't ideal for discovering new music videos because Youtube's algorithm usually suggests videos I've seen before, videos directly related to videos I've seen before, or content that is not music videos.
The best resource I've found for finding music videos is <a href="https://imvdb.com">The Internet Music Video Database</a>, but it doesn't feel like the solution.
IMVDb isn't an open database, meaning that it can't be copied, so users are at the mercy of the owners of the site to maintain and update it.
IMDb suffers from the same problem, but with such a large userbase and the backing of Amazon, this isn't really an issue for most users.
However, IMVDb has not been responsive to my submissions and is hard to navigate/search, despite having been online <a href="https://web.archive.org/web/20121022172925/http://imvdb.com/">for 7 years</a>.
Any work that I contribute to it belongs to someone else.
</p>

<p>
Maybe I'll try my hand at building a music video database with open licensing and improved discovery.
</p>
</article>
<?php include "2019/articles/read-everything-free.html"; ?>
<div id="subsequentbox">
	<div class="list">
		<h2>Things I've written</h2>
		<ul>
			<li>
				<a href="/2019/what-to-pack.html">What to Pack?</a>
			</li>
			<li>
				<a href="/2019/american-city-dialects-qgis.html">Mapping dialect variation among US cities in QGIS</a>
			</li>
		</ul>
		<a href="articles.html">view all posts ➡️</a>
	</div>
	<div class="list">
		<h2>Things I've made</h2>
		<ul>
			<li>
				<a href="2019/printkey">3D Print KW1, SC1, SC4 Keys</a>
			</li>
			<li>
				<a href="https://wordways.us">wordways.us</a>
			</li>
			<li>
				<a href="/nhoh">Named Houses of Harrisonburg</a>
			</li>
			<li>
				<a href="https://thehbarchive.org">The H-B Archive</a>
			</li>
			<li>
				<a href="blurred-fonts-project.html">Blurred Fonts Project</a>
			</li>
		</ul>
	</div>
</div>
</div>
<footer>
<div id="license">
<p>
<p xmlns:dct="http://purl.org/dc/terms/" xmlns:vcard="http://www.w3.org/2001/vcard-rdf/3.0#">
  <a rel="license"
     href="http://creativecommons.org/publicdomain/zero/1.0/">
<svg width="4.258em" height="1.5em" enable-background="new -0.5 -0.101 88 31" version="1.1" viewBox="-.5 -.101 88 31" xml:space="preserve" xmlns="http://www.w3.org/2000/svg">
	<title>🄍 (CC0)</title>
	<path d="m1.803 0.482 83.127 0.149c1.161 0 2.198-0.173 2.198 2.333l-0.103 27.556h-87.32v-27.658c0-1.236 0.118-2.38 2.098-2.38z" fill="#fff"/>
	
		<ellipse cx="13.887" cy="15.502" rx="11.101" ry="11.174" fill="#fff"/>
	
	<path d="M23.271,4.061c3.484,2.592,5.754,6.744,5.755,11.44c-0.001,4.272-1.88,8.095-4.842,10.705h62.853V4.061H23.271z"/>
	<g fill="#fff">
		<path d="m35.739 7.559c0.392 0 0.728 0.059 1.002 0.173 0.276 0.116 0.5 0.268 0.674 0.456 0.173 0.189 0.299 0.405 0.379 0.647 0.079 0.242 0.118 0.494 0.118 0.753 0 0.253-0.039 0.503-0.118 0.749-0.08 0.244-0.206 0.462-0.379 0.65-0.174 0.189-0.397 0.341-0.674 0.456-0.274 0.114-0.61 0.173-1.002 0.173h-1.452v2.267h-1.382v-6.324h2.834zm-0.379 2.976c0.158 0 0.312-0.012 0.457-0.035 0.147-0.023 0.276-0.069 0.388-0.137s0.201-0.164 0.269-0.288 0.101-0.287 0.101-0.487-0.033-0.362-0.101-0.487c-0.067-0.124-0.157-0.221-0.269-0.287-0.111-0.068-0.24-0.114-0.388-0.138-0.146-0.024-0.299-0.036-0.457-0.036h-1.073v1.896l1.073-1e-3z"/>
		<path d="m43.751 13.4c-0.476 0.417-1.133 0.625-1.972 0.625-0.851 0-1.509-0.207-1.976-0.62-0.466-0.412-0.699-1.052-0.699-1.913v-3.933h1.381v3.934c0 0.171 0.016 0.338 0.045 0.505 0.029 0.165 0.091 0.311 0.185 0.439 0.094 0.126 0.225 0.229 0.392 0.309 0.167 0.081 0.392 0.12 0.673 0.12 0.493 0 0.833-0.11 1.021-0.332s0.282-0.568 0.282-1.04v-3.935h1.382v3.934c-1e-3 0.855-0.238 1.49-0.714 1.907z"/>
		<path d="m49.07 7.559c0.3 0 0.572 0.027 0.818 0.081 0.244 0.054 0.457 0.14 0.633 0.261 0.177 0.121 0.312 0.282 0.41 0.482 0.096 0.201 0.146 0.45 0.146 0.745 0 0.318-0.072 0.584-0.216 0.796-0.146 0.212-0.357 0.388-0.639 0.523 0.387 0.112 0.676 0.31 0.865 0.589 0.189 0.281 0.286 0.62 0.286 1.015 0 0.319-0.062 0.595-0.187 0.828-0.123 0.232-0.289 0.423-0.496 0.571-0.209 0.148-0.445 0.257-0.713 0.327-0.269 0.07-0.541 0.105-0.822 0.105h-3.047v-6.323h2.962zm-0.175 2.56c0.246 0 0.448-0.059 0.607-0.178 0.158-0.118 0.236-0.309 0.236-0.576 0-0.147-0.025-0.269-0.078-0.363-0.053-0.093-0.123-0.168-0.211-0.221-0.09-0.053-0.189-0.091-0.305-0.109-0.115-0.022-0.232-0.032-0.355-0.032h-1.294v1.48l1.4-1e-3zm0.08 2.685c0.135 0 0.264-0.014 0.387-0.04s0.23-0.072 0.326-0.133c0.092-0.062 0.168-0.147 0.226-0.254 0.056-0.104 0.083-0.241 0.083-0.406 0-0.324-0.092-0.557-0.271-0.695-0.182-0.138-0.424-0.208-0.723-0.208h-1.505v1.738h1.479v-2e-3h-2e-3z"/>
		<path d="M54.143,7.559v5.156h3.062v1.168H52.76V7.559H54.143z"/>
		<path d="m59.748 7.559v6.324h-1.382v-6.324h1.382z"/>
		<path d="m65.451 9.247c-0.082-0.132-0.186-0.249-0.309-0.349-0.123-0.102-0.263-0.18-0.418-0.236-0.156-0.057-0.316-0.084-0.488-0.084-0.312 0-0.574 0.062-0.793 0.183-0.217 0.12-0.394 0.283-0.525 0.486-0.136 0.204-0.232 0.436-0.296 0.695-0.062 0.259-0.093 0.528-0.093 0.806 0 0.267 0.031 0.524 0.093 0.776 0.062 0.251 0.16 0.477 0.296 0.678 0.134 0.201 0.312 0.361 0.525 0.483 0.219 0.12 0.481 0.181 0.793 0.181 0.424 0 0.752-0.13 0.99-0.389 0.236-0.26 0.383-0.602 0.437-1.028h1.337c-0.034 0.396-0.126 0.753-0.271 1.072-0.146 0.318-0.342 0.591-0.582 0.815-0.238 0.225-0.521 0.396-0.845 0.513-0.323 0.119-0.678 0.178-1.065 0.178-0.479 0-0.914-0.084-1.297-0.252-0.385-0.169-0.709-0.398-0.973-0.695-0.265-0.295-0.468-0.642-0.607-1.04-0.142-0.399-0.211-0.829-0.211-1.289 0-0.473 0.069-0.911 0.211-1.316 0.141-0.404 0.344-0.758 0.607-1.059 0.264-0.302 0.588-0.536 0.973-0.708 0.384-0.172 0.815-0.258 1.297-0.258 0.348 0 0.676 0.051 0.981 0.15 0.308 0.102 0.583 0.248 0.827 0.44 0.243 0.191 0.443 0.43 0.604 0.712 0.158 0.283 0.259 0.608 0.301 0.975h-1.34c-0.024-0.163-0.077-0.31-0.159-0.44z"/>
		<path d="m35.615 16.418c0.405 0 0.782 0.062 1.131 0.192 0.35 0.13 0.651 0.324 0.906 0.585 0.255 0.26 0.455 0.586 0.599 0.975 0.144 0.391 0.216 0.849 0.216 1.371 0 0.463-0.059 0.888-0.176 1.277-0.118 0.391-0.295 0.727-0.532 1.012-0.238 0.281-0.534 0.504-0.89 0.668-0.354 0.16-0.772 0.242-1.254 0.242h-2.71v-6.322h2.71zm-0.096 5.154c0.199 0 0.393-0.031 0.581-0.098 0.188-0.062 0.354-0.173 0.502-0.323 0.146-0.151 0.264-0.347 0.352-0.59 0.088-0.241 0.132-0.536 0.132-0.886 0-0.317-0.031-0.606-0.093-0.863-0.062-0.256-0.162-0.479-0.304-0.659-0.141-0.183-0.326-0.323-0.559-0.421-0.231-0.098-0.517-0.146-0.858-0.146h-0.984v3.986h1.231z"/>
		<path d="m39.8 18.289c0.141-0.403 0.344-0.756 0.606-1.059 0.265-0.303 0.589-0.538 0.973-0.709 0.385-0.171 0.816-0.257 1.298-0.257 0.487 0 0.921 0.086 1.303 0.257 0.381 0.171 0.704 0.406 0.969 0.709 0.264 0.303 0.466 0.652 0.605 1.059 0.143 0.404 0.213 0.845 0.213 1.316 0 0.46-0.07 0.891-0.213 1.288-0.142 0.397-0.344 0.744-0.605 1.04-0.266 0.295-0.588 0.525-0.969 0.695-0.382 0.166-0.815 0.252-1.303 0.252-0.481 0-0.913-0.086-1.298-0.252-0.384-0.17-0.708-0.4-0.973-0.695-0.263-0.296-0.466-0.645-0.606-1.04-0.14-0.397-0.211-0.828-0.211-1.288 0-0.471 0.07-0.911 0.211-1.316zm1.262 2.09c0.062 0.252 0.16 0.479 0.295 0.68 0.135 0.2 0.312 0.359 0.527 0.482 0.218 0.121 0.481 0.183 0.792 0.183 0.312 0 0.576-0.062 0.792-0.183 0.218-0.121 0.394-0.281 0.529-0.482 0.134-0.2 0.231-0.428 0.295-0.68 0.062-0.25 0.092-0.508 0.092-0.774 0-0.276-0.03-0.547-0.092-0.806-0.062-0.262-0.161-0.492-0.295-0.696-0.136-0.201-0.312-0.365-0.529-0.485-0.216-0.121-0.48-0.184-0.792-0.184-0.311 0-0.574 0.062-0.792 0.184-0.216 0.12-0.393 0.284-0.527 0.485-0.135 0.204-0.233 0.437-0.295 0.696s-0.093 0.527-0.093 0.806c1e-3 0.266 0.032 0.524 0.093 0.774z"/>
		<path d="m49.092 16.418 1.471 4.348h0.02l1.393-4.348h1.942v6.322h-1.294v-4.48h-0.02l-1.539 4.48h-1.065l-1.54-4.437h-0.019v4.437h-1.293v-6.322h1.944z"/>
		<path d="m58.764 16.418 2.35 6.322h-1.434l-0.476-1.408h-2.351l-0.492 1.408h-1.391l2.377-6.322h1.417zm0.08 3.879-0.793-2.322h-0.018l-0.817 2.322h1.628z"/>
		<path d="m63.547 16.418v6.322h-1.382v-6.322h1.382z"/>
		<path d="m66.604 16.418 2.623 4.242h0.018v-4.242h1.294v6.322h-1.384l-2.611-4.234h-0.02v4.234h-1.294v-6.322h1.374z"/>
	</g>
	<path d="m85.852 0h-84.705c-0.908 0-1.647 0.744-1.647 1.658v28.969c0 0.207 0.167 0.373 0.372 0.373h87.256c0.205 0 0.372-0.166 0.372-0.373v-28.969c0-0.914-0.738-1.658-1.648-1.658zm-84.705 0.75h84.705c0.498 0 0.902 0.406 0.902 0.908v28.557h-86.509v-8.426-20.131c0-0.501 0.405-0.908 0.902-0.908z"/>
		<ellipse cx="14.156" cy="15.661" rx="11.004" ry="11.076" fill="#fff"/>
		<path d="m14.22 8.746c-3.862 0-4.834 3.669-4.834 6.779 0 3.111 0.971 6.779 4.834 6.779s4.834-3.67 4.834-6.779c0-3.111-0.971-6.779-4.834-6.779zm0 2.555c0.157 0 0.3 0.024 0.435 0.06 0.278 0.24 0.414 0.573 0.147 1.038l-2.572 4.76c-0.079-0.603-0.091-1.195-0.091-1.634 0-1.37 0.094-4.224 2.081-4.224zm1.926 2.193c0.137 0.731 0.155 1.493 0.155 2.03 0 1.37-0.094 4.223-2.08 4.223-0.156 0-0.301-0.017-0.435-0.049-0.025-0.01-0.049-0.019-0.074-0.025-0.04-0.012-0.084-0.024-0.122-0.041-0.442-0.188-0.721-0.531-0.319-1.139l2.875-4.999z"/>
		<path d="m14.195 3.748c-3.245 0-5.98 1.137-8.21 3.422-1.128 1.135-1.99 2.431-2.589 3.876-0.585 1.43-0.876 2.921-0.876 4.478 0 1.57 0.291 3.062 0.876 4.479s1.434 2.69 2.548 3.826c1.128 1.121 2.395 1.985 3.802 2.588 1.421 0.59 2.903 0.884 4.449 0.884 1.547 0 3.05-0.304 4.499-0.907 1.448-0.604 2.74-1.471 3.883-2.605 1.101-1.078 1.934-2.317 2.49-3.719 0.571-1.415 0.853-2.932 0.853-4.544 0-1.598-0.281-3.112-0.852-4.528-0.571-1.429-1.407-2.693-2.507-3.801-2.298-2.302-5.092-3.449-8.366-3.449zm0.049 2.119c2.646 0 4.904 0.944 6.784 2.836 0.906 0.912 1.6 1.954 2.073 3.119 0.473 1.164 0.713 2.398 0.713 3.703 0 2.707-0.92 4.952-2.744 6.746-0.948 0.927-2.012 1.638-3.196 2.128-1.17 0.489-2.375 0.732-3.63 0.732-1.268 0-2.481-0.239-3.638-0.717-1.156-0.489-2.193-1.191-3.113-2.104-0.92-0.925-1.629-1.97-2.13-3.135-0.487-1.178-0.738-2.391-0.738-3.653 0-1.276 0.251-2.497 0.738-3.662 0.501-1.178 1.211-2.235 2.13-3.175 1.824-1.876 4.077-2.818 6.751-2.818z"/>
</svg>
  </a>
  <br />
  To the extent possible under law,
  <a rel="dct:publisher"
     href="https://jacobhall.net">
    <span property="dct:title">Jacob Hall</span></a>
  has waived all copyright and related or neighboring rights to his work on
  <span property="dct:title">jacobhall.net</span>. Content adapted from other sources may be © its creators as noted.
<br>
This work is published from:
<span property="vcard:Country" datatype="dct:ISO3166"
      content="US" about="https://jacobhall.net">
  United States</span>.
</p>
</div>
</footer>
</body>
</html>
