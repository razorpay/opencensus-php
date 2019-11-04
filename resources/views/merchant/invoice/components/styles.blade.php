html, body {

margin: 0;
padding: 0;
width: 100%;
}

body {

font-family:'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif;
font-size:14px;
}

body * {

box-sizing: border-box;
-webkit-box-sizing: border-box;
-moz-box-sizing: border-box;
-o-box-sizing: border-box;
}

.foot-note, .invoice-box {

max-width:800px;
margin:auto;
}

.invoice-box {
padding:30px;
border:1px solid #eee;
box-shadow:0 0 10px rgba(0, 0, 0, .15);
line-height:24px;
color:#555;
position: relative;
}

.invoice-box div.page-title {

position: absolute;
top: 0;
left: 0;
padding: 2px;
padding-bottom: 20px;
width: 100%;
text-transform: uppercase;
text-align: center;
color: #AAA;
}

.foot-note {

font-size: 10px;
text-align: center;
margin-top: 10px;
margin-bottom: 10px;
}

.invoice-box table{
width:100%;
line-height:inherit;
text-align:left;
border-collapse: collapse;
}

.invoice-box table th {

background-color: #eee;
border-bottom: 1px solid #ddd;
}

.invoice-box table td, .invoice-box table th{
padding:5px 8px;
vertical-align:top;
}

.invoice-box table th {

vertical-align: middle;
}

.invoice-box table td.sno {

padding: 5px 12px;
}

.invoice-box table td.tax,
.invoice-box table td.amount,
.invoice-box table td.grand-total {

white-space: nowrap;
}

.invoice-box table tr.top table td{
padding-bottom:20px;
}

.invoice-box table tr.top table td.title{
font-size:45px;
line-height:45px;
color:#333;
}

.invoice-box table tr.top table td.title img.logo{

display: block;
width:100%;
max-width:200px;
}

.invoice-box table tr.information table td{
padding-bottom:40px;
}

.invoice-box table th.heading td{
background:#eee;
border-bottom:1px solid #ddd;
font-weight:bold;
}

.invoice-box table tr.details td{
padding-bottom:20px;
}

.invoice-box table tr.item td{
border-bottom:1px solid #eee;
}

.invoice-box table tr.item.last td{
border-bottom:none;
}

.invoice-box table tr.total td {
border-top:2px solid #eee;
font-weight:bold;
}

.invoice-box table tr.total td.empty {

border-top: none;
}

.text-right {

text-align: right;
}

.text-center {

text-align: center;
}

.text-left {

text-align: left !important;
}

.font-bold {

font-weight: bold;
}

/*
.code {

font-family: "Courier New", Courier, monospace;
}*/

div.bank-details {

width: 100%;
}

div.bank-details > table {

margin-top: 10px auto 0 auto;
}

div.bank-details table thead th {

background-color: transparent;
font-weight: bold;
}

div.bank-details table td.lesser-width {

width: 1%;
white-space: nowrap;
}

div.bank-details table td {

vertical-align: middle;
}

@media only print {

body {

font-size: 12px;
line-height: 15px;
}

.invoice-box {

padding: 15px;
}

.invoice-box table td {

padding: 0 2px;
}

.invoice-box table th {

padding-left: 0;
padding-right: 0;
}

.invoice-box table tr.top table td{

padding-bottom:10px;
}

.invoice-box table tr.information table td{

padding-bottom:20px;
}

.invoice-box table td.sno, .invoice-box table th.sno {

padding: 0 12px;
}

div.bank-details table td.lesser-width.seperator {

padding: 2px;
}

.foot-note {

margin-bottom: 0;
page-break-after: always;
}
}

@media only screen and (max-width: 600px) {
.invoice-box table tr.top table td{
width:100%;
display:block;
text-align:center;
}

.invoice-box table tr.information table td{
width:100%;
display:block;
text-align:center;
}
}
