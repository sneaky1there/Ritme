document.addEventListener('DOMContentLoaded',()=>{
  const root=document.querySelector('[data-nutrition-chart]');
  if(!root)return;
  const rows=JSON.parse(root.dataset.nutritionChart||'[]');
  const canvas=document.getElementById('nutrition-chart');
  if(!canvas||!rows.length){root.querySelector('.chart-box').innerHTML='<p class="empty">Nog geen maandgegevens.</p>';return;}
  new Chart(canvas,{type:'line',data:{
    labels:rows.map(r=>r.date.slice(5).split('-').reverse().join('-')),
    datasets:[
      {label:'Calorieën',data:rows.map(r=>r.calories_kcal),borderColor:'#24694f',backgroundColor:'#24694f22',tension:.25,yAxisID:'kcal'},
      {label:'Eiwit (g)',data:rows.map(r=>r.protein_g),borderColor:'#be912e',tension:.25,yAxisID:'grams'},
      {label:'Koolhydraten (g)',data:rows.map(r=>r.carbohydrates_g),borderColor:'#5577aa',tension:.25,yAxisID:'grams'},
      {label:'Vet (g)',data:rows.map(r=>r.fat_g),borderColor:'#a75d50',tension:.25,yAxisID:'grams'}
    ]},
    options:{responsive:true,maintainAspectRatio:false,interaction:{mode:'index',intersect:false},scales:{
      kcal:{position:'left',beginAtZero:true,title:{display:true,text:'kcal'}},
      grams:{position:'right',beginAtZero:true,title:{display:true,text:'gram'},grid:{drawOnChartArea:false}}
    }}
  });
});
