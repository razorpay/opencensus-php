<!doctype html>
<html>
<head>
<title>Razorpay - Checkout Testing page</title>
<script>
var _self="undefined"!=typeof window?window:"undefined"!=typeof WorkerGlobalScope&&self instanceof WorkerGlobalScope?self:{},Prism=function(){var e=/\blang(?:uage)?-(\w+)\b/i,t=0,n=_self.Prism={manual:_self.Prism&&_self.Prism.manual,util:{encode:function(e){return e instanceof a?new a(e.type,n.util.encode(e.content),e.alias):"Array"===n.util.type(e)?e.map(n.util.encode):e.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/\u00a0/g," ")},type:function(e){return Object.prototype.toString.call(e).match(/\[object (\w+)\]/)[1]},objId:function(e){return e.__id||Object.defineProperty(e,"__id",{value:++t}),e.__id},clone:function(e){var t=n.util.type(e);switch(t){case"Object":var a={};for(var r in e)e.hasOwnProperty(r)&&(a[r]=n.util.clone(e[r]));return a;case"Array":return e.map&&e.map(function(e){return n.util.clone(e)})}return e}},languages:{extend:function(e,t){var a=n.util.clone(n.languages[e]);for(var r in t)a[r]=t[r];return a},insertBefore:function(e,t,a,r){r=r||n.languages;var i=r[e];if(2==arguments.length){a=arguments[1];for(var l in a)a.hasOwnProperty(l)&&(i[l]=a[l]);return i}var o={};for(var s in i)if(i.hasOwnProperty(s)){if(s==t)for(var l in a)a.hasOwnProperty(l)&&(o[l]=a[l]);o[s]=i[s]}return n.languages.DFS(n.languages,function(t,n){n===r[e]&&t!=e&&(this[t]=o)}),r[e]=o},DFS:function(e,t,a,r){r=r||{};for(var i in e)e.hasOwnProperty(i)&&(t.call(e,i,e[i],a||i),"Object"!==n.util.type(e[i])||r[n.util.objId(e[i])]?"Array"!==n.util.type(e[i])||r[n.util.objId(e[i])]||(r[n.util.objId(e[i])]=!0,n.languages.DFS(e[i],t,i,r)):(r[n.util.objId(e[i])]=!0,n.languages.DFS(e[i],t,null,r)))}},plugins:{},highlightAll:function(e,t){var a={callback:t,selector:'code[class*="language-"], [class*="language-"] code, code[class*="lang-"], [class*="lang-"] code'};n.hooks.run("before-highlightall",a);for(var r,i=a.elements||document.querySelectorAll(a.selector),l=0;r=i[l++];)n.highlightElement(r,e===!0,a.callback)},highlightElement:function(t,a,r){for(var i,l,o=t;o&&!e.test(o.className);)o=o.parentNode;o&&(i=(o.className.match(e)||[,""])[1].toLowerCase(),l=n.languages[i]),t.className=t.className.replace(e,"").replace(/\s+/g," ")+" language-"+i,o=t.parentNode,/pre/i.test(o.nodeName)&&(o.className=o.className.replace(e,"").replace(/\s+/g," ")+" language-"+i);var s=t.textContent,u={element:t,language:i,grammar:l,code:s};if(n.hooks.run("before-sanity-check",u),!u.code||!u.grammar)return u.code&&(n.hooks.run("before-highlight",u),u.element.textContent=u.code,n.hooks.run("after-highlight",u)),n.hooks.run("complete",u),void 0;if(n.hooks.run("before-highlight",u),a&&_self.Worker){var g=new Worker(n.filename);g.onmessage=function(e){u.highlightedCode=e.data,n.hooks.run("before-insert",u),u.element.innerHTML=u.highlightedCode,r&&r.call(u.element),n.hooks.run("after-highlight",u),n.hooks.run("complete",u)},g.postMessage(JSON.stringify({language:u.language,code:u.code,immediateClose:!0}))}else u.highlightedCode=n.highlight(u.code,u.grammar,u.language),n.hooks.run("before-insert",u),u.element.innerHTML=u.highlightedCode,r&&r.call(t),n.hooks.run("after-highlight",u),n.hooks.run("complete",u)},highlight:function(e,t,r){var i=n.tokenize(e,t);return a.stringify(n.util.encode(i),r)},matchGrammar:function(e,t,a,r,i,l,o){var s=n.Token;for(var u in a)if(a.hasOwnProperty(u)&&a[u]){if(u==o)return;var g=a[u];g="Array"===n.util.type(g)?g:[g];for(var c=0;c<g.length;++c){var h=g[c],f=h.inside,d=!!h.lookbehind,m=!!h.greedy,p=0,y=h.alias;if(m&&!h.pattern.global){var v=h.pattern.toString().match(/[imuy]*$/)[0];h.pattern=RegExp(h.pattern.source,v+"g")}h=h.pattern||h;for(var b=r,k=i;b<t.length;k+=t[b].length,++b){var w=t[b];if(t.length>e.length)return;if(!(w instanceof s)){h.lastIndex=0;var _=h.exec(w),P=1;if(!_&&m&&b!=t.length-1){if(h.lastIndex=k,_=h.exec(e),!_)break;for(var A=_.index+(d?_[1].length:0),j=_.index+_[0].length,x=b,O=k,S=t.length;S>x&&(j>O||!t[x].type&&!t[x-1].greedy);++x)O+=t[x].length,A>=O&&(++b,k=O);if(t[b]instanceof s||t[x-1].greedy)continue;P=x-b,w=e.slice(k,O),_.index-=k}if(_){d&&(p=_[1].length);var A=_.index+p,_=_[0].slice(p),j=A+_.length,N=w.slice(0,A),C=w.slice(j),E=[b,P];N&&(++b,k+=N.length,E.push(N));var L=new s(u,f?n.tokenize(_,f):_,y,_,m);if(E.push(L),C&&E.push(C),Array.prototype.splice.apply(t,E),1!=P&&n.matchGrammar(e,t,a,b,k,!0,u),l)break}else if(l)break}}}}},tokenize:function(e,t){var a=[e],r=t.rest;if(r){for(var i in r)t[i]=r[i];delete t.rest}return n.matchGrammar(e,a,t,0,0,!1),a},hooks:{all:{},add:function(e,t){var a=n.hooks.all;a[e]=a[e]||[],a[e].push(t)},run:function(e,t){var a=n.hooks.all[e];if(a&&a.length)for(var r,i=0;r=a[i++];)r(t)}}},a=n.Token=function(e,t,n,a,r){this.type=e,this.content=t,this.alias=n,this.length=0|(a||"").length,this.greedy=!!r};if(a.stringify=function(e,t,r){if("string"==typeof e)return e;if("Array"===n.util.type(e))return e.map(function(n){return a.stringify(n,t,e)}).join("");var i={type:e.type,content:a.stringify(e.content,t,r),tag:"span",classes:["token",e.type],attributes:{},language:t,parent:r};if("comment"==i.type&&(i.attributes.spellcheck="true"),e.alias){var l="Array"===n.util.type(e.alias)?e.alias:[e.alias];Array.prototype.push.apply(i.classes,l)}n.hooks.run("wrap",i);var o=Object.keys(i.attributes).map(function(e){return e+'="'+(i.attributes[e]||"").replace(/"/g,"&quot;")+'"'}).join(" ");return"<"+i.tag+' class="'+i.classes.join(" ")+'"'+(o?" "+o:"")+">"+i.content+"</"+i.tag+">"},!_self.document)return _self.addEventListener?(_self.addEventListener("message",function(e){var t=JSON.parse(e.data),a=t.language,r=t.code,i=t.immediateClose;_self.postMessage(n.highlight(r,n.languages[a],a)),i&&_self.close()},!1),_self.Prism):_self.Prism;var r=document.currentScript||[].slice.call(document.getElementsByTagName("script")).pop();return r&&(n.filename=r.src,!document.addEventListener||n.manual||r.hasAttribute("data-manual")||("loading"!==document.readyState?window.requestAnimationFrame?window.requestAnimationFrame(n.highlightAll):window.setTimeout(n.highlightAll,16):document.addEventListener("DOMContentLoaded",n.highlightAll))),_self.Prism}();"undefined"!=typeof module&&module.exports&&(module.exports=Prism),"undefined"!=typeof global&&(global.Prism=Prism);Prism.languages.clike={comment:[{pattern:/(^|[^\\])\/\*[\s\S]*?\*\//,lookbehind:!0},{pattern:/(^|[^\\:])\/\/.*/,lookbehind:!0}],string:{pattern:/(["'])(\\(?:\r\n|[\s\S])|(?!\1)[^\\\r\n])*\1/,greedy:!0},"class-name":{pattern:/((?:\b(?:class|interface|extends|implements|trait|instanceof|new)\s+)|(?:catch\s+\())[a-z0-9_\.\\]+/i,lookbehind:!0,inside:{punctuation:/(\.|\\)/}},keyword:/\b(if|else|while|do|for|return|in|instanceof|function|new|try|throw|catch|finally|null|break|continue)\b/,"boolean":/\b(true|false)\b/,"function":/[a-z0-9_]+(?=\()/i,number:/\b-?(?:0x[\da-f]+|\d*\.?\d+(?:e[+-]?\d+)?)\b/i,operator:/--?|\+\+?|!=?=?|<=?|>=?|==?=?|&&?|\|\|?|\?|\*|\/|~|\^|%/,punctuation:/[{}[\];(),.:]/};Prism.languages.javascript=Prism.languages.extend("clike",{keyword:/\b(as|async|await|break|case|catch|class|const|continue|debugger|default|delete|do|else|enum|export|extends|finally|for|from|function|get|if|implements|import|in|instanceof|interface|let|new|null|of|package|private|protected|public|return|set|static|super|switch|this|throw|try|typeof|var|void|while|with|yield)\b/,number:/\b-?(0x[\dA-Fa-f]+|0b[01]+|0o[0-7]+|\d*\.?\d+([Ee][+-]?\d+)?|NaN|Infinity)\b/,"function":/[_$a-zA-Z\xA0-\uFFFF][_$a-zA-Z0-9\xA0-\uFFFF]*(?=\()/i,operator:/-[-=]?|\+[+=]?|!=?=?|<<?php echo"<?";?>=?|>>?>?=?|=(?:==?|>)?|&[&=]?|\|[|=]?|\*\*?=?|\/=?|~|\^=?|%=?|\?|\.{3}/}),Prism.languages.insertBefore("javascript","keyword",{regex:{pattern:/(^|[^\/])\/(?!\/)(\[.+?]|\\.|[^\/\\\r\n])+\/[gimyu]{0,5}(?=\s*($|[\r\n,.;})]))/,lookbehind:!0,greedy:!0}}),Prism.languages.insertBefore("javascript","string",{"template-string":{pattern:/`(?:\\\\|\\?[^\\])*?`/,greedy:!0,inside:{interpolation:{pattern:/\$\{[^}]+\}/,inside:{"interpolation-punctuation":{pattern:/^\$\{|\}$/,alias:"punctuation"},rest:Prism.languages.javascript}},string:/[\s\S]+/}}}),Prism.languages.markup&&Prism.languages.insertBefore("markup","tag",{script:{pattern:/(<script[\s\S]*?>)[\s\S]*?(?=<\/script>)/i,lookbehind:!0,inside:Prism.languages.javascript,alias:"language-javascript"}}),Prism.languages.js=Prism.languages.javascript;
function CodeFlask(){}CodeFlask.prototype.run=function(e,t){var n=document.querySelectorAll(e);if(n.length>1)throw"CodeFlask.js ERROR: run() expects only one element, "+n.length+" given. Use .runAll() instead.";this.scaffold(n[0],!1,t)},CodeFlask.prototype.runAll=function(e,t){this.update=null,this.onUpdate=null;var n,a=document.querySelectorAll(e);for(n=0;n<a.length;n++)this.scaffold(a[n],!0,t)},CodeFlask.prototype.scaffold=function(e,t,n){var a=document.createElement("TEXTAREA"),l=document.createElement("PRE"),o=document.createElement("CODE"),i=e.textContent;n.language=this.handleLanguage(n.language),this.defaultLanguage=e.dataset.language||n.language||"markup",t||(this.textarea=a,this.highlightCode=o),e.classList.add("CodeFlask"),a.classList.add("CodeFlask__textarea"),l.classList.add("CodeFlask__pre"),o.classList.add("CodeFlask__code"),o.classList.add("language-"+this.defaultLanguage),/iPad|iPhone|iPod/.test(navigator.platform)&&(o.style.paddingLeft="3px"),e.innerHTML="",e.appendChild(a),e.appendChild(l),l.appendChild(o),a.value=i,this.renderOutput(o,a),Prism.highlightAll(),this.handleInput(a,o,l),this.handleScroll(a,l)},CodeFlask.prototype.renderOutput=function(e,t){e.innerHTML=t.value.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")+"\n"},CodeFlask.prototype.handleInput=function(e,t,n){var a,l,o,i=this;e.addEventListener("input",function(e){a=this,i.renderOutput(t,a),Prism.highlightAll()}),e.addEventListener("keydown",function(e){a=this,l=a.selectionStart,o=a.value,9===e.keyCode&&(a.value=o.substring(0,l)+"    "+o.substring(l,a.value.length),a.selectionStart=l+4,a.selectionEnd=l+4,e.preventDefault(),t.innerHTML=a.value.replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")+"\n",Prism.highlightAll())})},CodeFlask.prototype.handleScroll=function(e,t){e.addEventListener("scroll",function(){roundedScroll=Math.floor(this.scrollTop),navigator.userAgent.toLowerCase().indexOf("firefox")<0&&(this.scrollTop=roundedScroll),t.style.top="-"+roundedScroll+"px"})},CodeFlask.prototype.handleLanguage=function(e){return e.match(/html|xml|xhtml|svg/)?"markup":e.match(/js/)?"javascript":e},CodeFlask.prototype.onUpdate=function(e){if("function"!=typeof e)throw"CodeFlask.js ERROR: onUpdate() expects function, "+typeof e+" given instead.";this.textarea.addEventListener("input",function(t){e(this.value)})},CodeFlask.prototype.update=function(e){var t=document.createEvent("HTMLEvents");this.textarea.value=e,this.renderOutput(this.highlightCode,this.textarea),Prism.highlightAll(),t.initEvent("input",!1,!0),this.textarea.dispatchEvent(t)};
</script>
<style>
::-webkit-scrollbar{width:0}
code[class*="language-"],pre[class*="language-"]{color:#f8f8f2;background:none;text-shadow:0 1px rgba(0,0,0,.3)}pre[class*="language-"]{overflow:auto}:not(pre)>code[class*="language-"]{padding:.1em;border-radius:.3em;white-space:normal}.token.comment,.token.prolog,.token.doctype,.token.cdata{color:slategray}.token.punctuation{color:#f8f8f2}.namespace{opacity:.7}.token.property,.token.tag,.token.constant,.token.symbol,.token.deleted{color:#f92672}.token.boolean,.token.number{color:#ae81ff}.token.selector,.token.attr-name,.token.string,.token.char,.token.builtin,.token.inserted{color:#a6e22e}.token.operator,.token.entity,.token.url,.language-css .token.string,.style .token.string,.token.variable{color:#f8f8f2}.token.atrule,.token.attr-value,.token.function{color:#e6db74}.token.keyword{color:#66d9ef}.token.regex,.token.important{color:#fd971f}.token.important,.token.bold{font-weight:700}.token.italic{font-style:italic}.token.entity{cursor:help}
.CodeFlask__pre,.CodeFlask__textarea{text-align:left;white-space:pre-wrap;word-spacing:normal;word-break:normal;word-wrap:normal;line-height:1.5;tab-size:4;hyphens:nonebox-sizing:border-box;position:absolute;top:0;left:0;width:50%;padding:16px;border:none;margin:0;font-family:Consolas,Monaco,'Andale Mono','Ubuntu Mono',monospace;font-size:13px;background:0 0}.CodeFlask__textarea{border:none;outline:0;resize:none;color:transparent;caret-color:#fff;z-index:1;height:100%;-webkit-overflow-scrolling:touch}.CodeFlask__pre{z-index:2;pointer-events:none;overflow-y:auto;margin:0;min-height:100%}.CodeFlask__code{font-size:inherit;font-family:inherit;color:inherit;display:block}.CodeFlask__is-code{white-space:pre}
body {
  background:#272822;
  margin: 0;
}
iframe {
  position: absolute;
  right: 0;
  width: 50%;
  top: 0;
  height: 100%;
  border: 0;
}
#target {
  display: none;
}
#keys {
  position: fixed;
  bottom: 10px;
  right: 10px;
  z-index: 1;
  color: rgba(255,255,255,.8);
  background: rgba(255,255,255,.04);
  border-radius: 4px;
  font-size: 14px;
  border: 1px solid rgba(255,255,255,.08);
  padding: 2px 6px;
  cursor: pointer;
  font-size: 12px;
}
#keys:hover {
  color: #fff;
}
#keys:hover:before {
  color: rgba(255,255,255,.5);
  content: 'Press `Command + Return` to execute.';
}
</style>
</head>
<body>
<div id="keys">
⌘ ⏎
</div>
<iframe></iframe>
<div id="code">var test_key = 'rzp_test_1DP5mmOlF5G5ag';
var live_key = 'rzp_live_4dngATlGkC5Wap';

var key = live_key;

<?php if ($_SERVER['HTTP_HOST'] !== "api.razorpay.com"): ?>
var Razorpay = {
  config: {
    api: '/'
  }
};
<?php endif; ?>

var options = {
  key: key,
  amount: 100,
  handler: resp => alert(resp.razorpay_payment_id),
  prefill: {
    email: 'vivek@razorpay.com'
  }
}</div>
<script>
var $ = document.querySelector.bind(document);
if (localStorage.code) {
  $('#code').innerHTML = localStorage.code;
}
var flask=new CodeFlask;flask.run('#code',{language:'javascript'})
var t = $('textarea')
var i = $('iframe')
var x = $('#target')
t.setAttribute('spellcheck', 'false')
t.oninput = () => {localStorage.code = t.value}
t.onkeypress = e => {if(e.code==="Enter"&&(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)){
  i.contentDocument.write(`
<script>${t.value}<\/script>
<script src="https://checkout.razorpay.com/v1/checkout.js" onload="Razorpay.open(options)"><\/script>
`)
  i.contentDocument.close();
}}
</script>
