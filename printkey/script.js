function makelink() {
  var linkbase = "genkey.php?";
  var type = "type=" + document.querySelector('input[name=type]:checked').value;
  var bits = "&1=" + document.getElementById('bit1').value + "&2=" + document.getElementById('bit2').value + "&3=" + document.getElementById('bit3').value + "&4=" + document.getElementById('bit4').value + "&5=" + document.getElementById('bit5').value;
  if (document.querySelector('input[name=type]:checked').value > 1) {
    document.getElementById('bit6').style.display = 'inline';
    bits += "&6=" + document.getElementById('bit6').value;
  } else {
    document.getElementById('bit6').style.display = 'none';
  }
  document.getElementById('downloadlink').href = linkbase + type + bits;
}
