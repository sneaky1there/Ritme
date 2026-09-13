"use strict";
const exerciseGrid=document.querySelector('[data-exercise-charts]');
if(exerciseGrid&&typeof Chart!=='undefined'){
 const exercises=JSON.parse(exerciseGrid.dataset.exerciseCharts);
 Chart.defaults.font.family='system-ui, sans-serif';Chart.defaults.color='#65726d';
 exercises.forEach((exercise,index)=>{
  const canvas=document.getElementById('exercise-chart-'+index);if(!canvas)return;
  new Chart(canvas,{type:'line',data:{labels:exercise.points.map(point=>point.date.split('-').reverse().join('-')),datasets:[{label:'Zwaarste gewicht',data:exercise.points.map(point=>point.weight),borderColor:'#24694f',backgroundColor:'#24694f22',borderWidth:3,pointRadius:4,pointHoverRadius:6,tension:.2,fill:true}]},options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},plugins:{legend:{display:false},tooltip:{callbacks:{title:items=>'Datum: '+items[0].label,label:item=>'Type: normal · '+item.parsed.y.toLocaleString('nl-NL',{maximumFractionDigits:2})+' kg'}}},scales:{x:{grid:{display:false},title:{display:true,text:'Datum'}},y:{beginAtZero:false,grid:{color:'#edf0eb'},title:{display:true,text:'Zwaarste gewicht (kg)'}}}}});
 });
}
