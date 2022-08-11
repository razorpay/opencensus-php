<style>
*{
  box-sizing:border-box;
  margin:0;
  padding:0;
}

body{
  background:#f5f5f5;
  overflow:hidden;
  text-align:center;
  height:100%;
  white-space:nowrap;
  margin:0;
  padding:0;
  font-family:-apple-system, BlinkMacSystemFont,ubuntu,verdana,helvetica,sans-serif;
}

#bg {
  position:absolute;
  bottom:50%;
  width:100%;
  height:50%;
  background:{{$data['theme']['color']}};
  margin-bottom:90px;
}
#cntnt {
  position:relative;
  width:100%;
  vertical-align: middle;
  display: inline-block;
  margin: auto;
  max-width:420px;
  min-width:280px;
  height:95%;
  max-height:360px;
  background:#fff;
  z-index:9999;
  box-shadow:0 0 20px 0 rgba(0,0,0,0.16);
  border-radius:4px;
  overflow:hidden;
  padding:24px;
  box-sizing:border-box;
  text-align:left;
}
#ftr {
  position:absolute;
  left:0;
  right:0;
  bottom:0;
  height:80px;
  background:#f5f5f5;
  text-align:center;
  color:#212121;
  font-size:14px;
  letter-spacing:-0.3px;
  @if ($data['nobranding'])
    display: none;
  @endif
}

#ftr_new {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0 24px;
  position:absolute;
  left:0;
  right:0;
  bottom:0;
  height:80px;
  background:#f5f5f5;
  text-align:center;
  color:#212121;
  font-size:14px;
  letter-spacing:-0.3px;
  @if ($data['nobranding'])
    display: none;
  @endif
}

#ldr {
  width:100%;
  height:3px;
  position:relative;
  margin-top:16px;
  border-radius:3px;
  overflow:hidden;
}

#ldr::before, #ldr::after {
  content:'';
  position:absolute;
  top:0;
  bottom:0;
  width:100%;
}

#ldr::before {
  top:1px;
  border-top:1px solid #bcbcbc;
}

#ldr::after {
  background:{{$data['theme']['color']}};
  width:0%;
  transition:20s cubic-bezier(0,0.1,0,1);
}

.loaded #ldr::after {
  width:90%;
}

#logo {
  width:48px;
  height:48px;
  padding:8px;
  border:1px solid #e5e5e5;
  border-radius:3px;
  text-align:center;
}

#hdr {
  min-height:48px;
  position:relative;
}

#logo, #name, #amt {
  display:inline-block;
  vertical-align:middle;
  letter-spacing:-0.5px;
}

#amt {
  position:absolute;
  right:0;
  top:0;
  background:#fff;
  color:#212121;
}

#name {
  line-height:48px;
  margin-left:12px;
  font-size:16px;
  max-width:140px;
  overflow:hidden;
  text-overflow:ellipsis;
  color:#212121;
}

#logo+#name{
  line-height:20px;
}

#txt {
  height:200px;
  text-align:center;
}

#title {
  font-size:20px;
  line-height:24px;
  margin-bottom:8px;
  letter-spacing:-0.3px;
}

#msg, #cncl {
  font-size:14px;
  line-height:20px;
  color:#757575;
  margin-bottom:8px;
  letter-spacing:-0.3px;
}

#cncl {
  text-decoration:underline;
  cursor:pointer;
}

#logo img {
  max-width:100%;
  max-height:100%;
  vertical-align:middle;
}

@media (max-height:580px), (max-width:420px) {
  #bg{
     display:none;
  }
  body {
    background:{{$data['theme']['color']}};
  }
}

@media (max-width:420px){
  #cntnt {
    padding:16px;
    width:95%;
  }
  #name {
    margin-left:8px;
  }
}
@media (max-height:580px), (max-width:420px) {
  #ftr_new{
     padding: 0 16px;
  }
}
</style>
