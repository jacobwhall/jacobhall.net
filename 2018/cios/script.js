var poemlinks = [
	"poems/cardboardbox.html",
	"poems/easy.html",
	"poems/stemmanuels.html",
	"poems/manuscript.html",
	"poems/wildbirds.html",
	"poems/openletter.html",
	"poems/magic.html",
	"poems/thanksgiving.html",
	"poems/anotheramend.html",
	"poems/blueness.html",
	"intro.html",
	"bio.html"
];

var moreinfolinks = [
	"https://www.youtube.com/watch?v=rhPEGwydnpw",
	"https://www.youtube.com/watch?v=ezaBqCf0hv0",
	"https://www.youtube.com/watch?v=XU8OCzFfRlY"
];

function link(targets) {
	var poem = targets[Math.floor(Math.random()*targets.length)];
	console.log("Picked poem " + poem + " from " + targets);
	var xhr = new XMLHttpRequest();
	xhr.open('GET', poemlinks[poem-1], true);
	xhr.onreadystatechange= function() {
		if (this.readyState!==4) return;
		if (this.status!==200) return; // or whatever error handling you want
		document.getElementById('poembox').innerHTML= this.responseText;
		if (targets[0] == [12]) {
			var moreinfolink = moreinfolinks[Math.floor(Math.random()*moreinfolinks.length)];
			var a = document.getElementById('moreinfo');
			a.href = moreinfolink;
		}
	};
	xhr.send();
}

link([11]);
