"use strict";
const qr = document.getElementById('pair-qr');
if(qr && typeof QRCode !== 'undefined') new QRCode(qr,{text:qr.dataset.link,width:240,height:240,correctLevel:QRCode.CorrectLevel.M});
