<style>
@keyframes lo{to{transform:rotate(360deg)}}@-webkit-keyframes lo{to{-webkit-transform:rotate(360deg)}}
.loader{height:24px;width:24px;border-radius:50%;display:inline-block;
  animation:lo .8s infinite linear;-webkit-animation:lo .8s infinite linear;
  transition:0.3s;-webkit-transition:0.3s;
  opacity:0;border:2px solid #3395FF;border-top-color:transparent}
.vis{opacity:1}
</style>
<div class="loader vis" style="position:absolute;top:115px;left:50%;margin-left:-12px"></div>
