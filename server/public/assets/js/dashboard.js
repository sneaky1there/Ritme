"use strict";
const container = document.querySelector('[data-chart]');
if (container && typeof Chart !== 'undefined') {
  const data = JSON.parse(container.dataset.chart);
  Chart.defaults.font.family = 'system-ui, sans-serif';
  Chart.defaults.color = '#65726d';
  const dates = rows => rows.map(r => (r.date || r.week).slice(5).split('-').reverse().join('-'));
  function draw(id, rows, series, type = 'line', unit = '') {
    const hasData = series.some(s => rows.some(r => r[s.key] !== null && r[s.key] !== undefined));
    const canvas = document.getElementById('chart-' + id);
    if (!hasData) {
      const message = document.createElement('p'); message.className = 'empty';
      message.textContent = 'Nog geen metingen in deze periode.';
      canvas.replaceWith(message); return;
    }
    new Chart(canvas, {type, data: {labels: dates(rows), datasets: series.map((s,i) => ({
      label: s.label, data: rows.map(r => r[s.key]), borderColor: i ? '#a1bfae' : '#24694f',
      backgroundColor: i ? '#a1bfae44' : '#24694f22', borderWidth: i ? 3 : 2,
      pointRadius: rows.length > 20 ? (i ? 0 : 2) : 3, tension: 0.25, spanGaps: false, borderRadius: 5
    }))}, options: {responsive: true, maintainAspectRatio: false,
      interaction: {mode:'index',intersect:false},
      plugins:{legend:{display:series.length>1,position:'bottom'},tooltip:{callbacks:{label:ctx=>ctx.dataset.label+': '+(ctx.parsed.y===null?'—':ctx.parsed.y.toLocaleString('nl-NL',{maximumFractionDigits:2})+unit)}}},
      scales:{x:{grid:{display:false},ticks:{maxTicksLimit:8}},y:{beginAtZero:type==='bar',grid:{color:'#edf0eb'},ticks:{precision:id==='training'?0:undefined}}}
    }});
  }
  draw('weight',data.days,[{key:'weight',label:'Dagelijks (kg)'},{key:'rolling',label:'7-daags gemiddelde'}],'line',' kg');
  draw('navel',data.days,[{key:'navel',label:'Navelomtrek'}],'line',' cm');
  draw('steps',data.weeks,[{key:'steps',label:'Stappen'}],'bar');
  draw('sleep',data.weeks,[{key:'sleep',label:'Gemiddelde tijd in bed'}],'bar',' uur');
  if(document.getElementById('chart-hc_sleep')) draw('hc_sleep',data.weeks,[{key:'hc_sleep',label:'Slaapregistratie'}],'bar',' uur');
  draw('training',data.weeks,[{key:'workouts',label:'Hevy-trainingen'}],'bar');
}
