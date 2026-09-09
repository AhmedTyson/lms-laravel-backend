/* Graph: layout, nodes, edges, filters, pan/zoom, minimap. Depends: data.js */
/* ============================== LAYOUT ============================== */
const byName = Object.fromEntries(TABLES.map(t=>[t.name,t]));
const W=250, GAPX=145, RH=24, HH=40, GAPY=26;
const depths=[...new Set(TABLES.map(t=>t.depth))].sort((a,b)=>a-b);
const pos={};
depths.forEach(d=>{
  const col=TABLES.filter(t=>t.depth===d);
  let y=30;
  col.forEach(t=>{
    const h=HH+t.cols.length*RH+8;
    pos[t.name]={x:30+d*(W+GAPX),y,w:W,h};
    y+=h+GAPY;
  });
});
const maxY=Math.max(...Object.values(pos).map(p=>p.y+p.h))+40;
const maxX=30+Math.max(...depths)*(W+GAPX)+W+40;

const canvas=document.getElementById('canvas'), svg=document.getElementById('edges'), wrap=document.getElementById('wrap');
canvas.style.width=maxX+'px'; canvas.style.height=maxY+'px';
svg.setAttribute('width',maxX); svg.setAttribute('height',maxY);
svg.setAttribute('viewBox',`0 0 ${maxX} ${maxY}`);

/* ============================== STATS (computed, not hardcoded) ============================== */
const totalCols = TABLES.reduce((s,t)=>s+t.cols.length,0);
const uniqueComposite = TABLES.reduce((s,t)=>s+t.idx.filter(i=>i.unique && i.cols.length>1).length,0);
document.getElementById('titleTag').textContent = `${TABLES.length} tables · IE / crow's-foot notation`;
document.getElementById('statsRow').innerHTML = `
  <div class="stat"><b>${TABLES.length}</b>tables</div>
  <div class="stat"><b>${RELS.length}</b>foreign-key relationships</div>
  <div class="stat"><b>${uniqueComposite}</b>race-closing unique indexes</div>
  <div class="stat"><b>${totalCols}</b>columns total</div>
  <div class="stat"><b>1</b>append-only ledger (permission_grants)</div>
  <div class="stat"><b>0</b>tables owned by Reporting — read-only</div>`;

document.getElementById('enumGrid').innerHTML = ENUMS.map(([n,v])=>
  `<div class="en">${n}</div><div class="vals">${v.join(' · ')}</div>`).join('');

/* ============================== MODULE CHIPS ============================== */
const activeModules = new Set();
const chipsEl = document.getElementById('moduleChips');
Object.entries(MODULES).forEach(([key,m])=>{
  const n = TABLES.filter(t=>t.module===key).length;
  const el = document.createElement('div');
  el.className='chip'; el.style.setProperty('--chipc', m.color);
  el.innerHTML = `<span class="sw" style="background:${m.color}"></span>${m.label} <span style="opacity:.6">${n}</span>`;
  el.onclick=()=>{ el.classList.toggle('on'); activeModules.has(key)?activeModules.delete(key):activeModules.add(key); applyFilters(); };
  chipsEl.appendChild(el);
});

/* ============================== RENDER NODES ============================== */
TABLES.forEach(t=>{
  const p = pos[t.name];
  const d = document.createElement('div');
  d.className='tbl'; d.id='n-'+t.name; d.style.left=p.x+'px'; d.style.top=p.y+'px';
  const rows = t.cols.map(c=>{
    const badges = `<span class="keys">${c.pk?'<span class="badge b-pk">PK</span>':''}${c.fk?'<span class="badge b-fk">FK</span>':''}</span>`;
    const typeShort = c.t.split('→')[0].trim();
    return `<li class="${c.pk?'is-pk':''} ${c.fk?'is-fk':''}" data-col="${c.n}"><span class="cn">${c.n}${c.nn?'<span class="nn"> ∗</span>':''}</span><span class="ct">${typeShort}</span>${badges}</li>`;
  }).join('');
  d.innerHTML = `<div class="thead" style="border-left-color:${MODULES[t.module].color}"><span class="t">${t.name}</span><span class="m">${MODULES[t.module].label}</span></div><ul>${rows}</ul>`;
  d.onmouseenter=()=>hoverHighlight(t.name,true);
  d.onmouseleave=()=>hoverHighlight(t.name,false);
  d.onclick=()=>select(t.name);
  d.ondblclick=(e)=>{ e.stopPropagation(); enterFocus(t.name); };
  canvas.appendChild(d);
});

/* ============================== RENDER EDGES ============================== */
function rowY(name,col){
  const p=pos[name], t=byName[name];
  const i=t.cols.findIndex(c=>c.n===col);
  return p.y+HH+(i<0?0:i)*RH+RH/2;
}
function glyph(kind,x,y,ang,color){
  if(kind==='bar') return `<line x1="${x}" y1="${y-6.5}" x2="${x}" y2="${y+6.5}" stroke="${color}" stroke-width="2" transform="rotate(${ang} ${x} ${y})"/>`;
  if(kind==='ring') return `<circle cx="${x}" cy="${y}" r="5" fill="${'var(--bg)'}" stroke="${color}" stroke-width="1.7"/>`;
  if(kind==='fork'){
    const L=13, a=(deg)=>{const r=((ang+deg)*Math.PI)/180;return [x+L*Math.cos(r),y+L*Math.sin(r)];};
    const [x1,y1]=a(-26),[x2,y2]=a(0),[x3,y3]=a(26);
    return `<path d="M${x},${y} L${x1},${y1} M${x},${y} L${x2},${y2} M${x},${y} L${x3},${y3}" stroke="${color}" stroke-width="1.7" fill="none"/>`;
  }
  return '';
}
function pill(cx,cy,text,color){
  const w = text.length*6.2+16;
  return `<g><rect x="${cx-w/2}" y="${cy-9.5}" width="${w}" height="19" rx="9.5" fill="#0e1320" stroke="${color}" stroke-opacity=".55"/><text x="${cx}" y="${cy+3.5}" text-anchor="middle" font-size="10" fill="#9aa2ba" font-family="Segoe UI, sans-serif">${text}</text></g>`;
}
function drawEdges(){
  let s='';
  RELS.forEach(([a,col,b,verb,opt,oto])=>{
    const A=pos[a];
    if(a===b){
      // self-reference: loop out to the right and back into the PK row
      const x1=A.x+A.w, y1=rowY(a,col);
      const yTop=A.y+HH+RH/2;
      const loopX=A.x+A.w+52;
      s += `<path data-a="${a}" data-b="${b}" d="M${x1},${y1} C${loopX},${y1} ${loopX},${yTop} ${x1},${yTop}" fill="none" stroke="#4a5a86" stroke-width="1.5" stroke-dasharray="4 3"/>`;
      s += glyph('fork', x1+2, y1, 0, '#8fa2d8');
      const ringX = x1+2;
      s += glyph('bar', yTop!==y1?ringX+11:ringX, yTop, 180, '#8fa2d8');
      s += glyph('ring', ringX, yTop, 180, '#8fa2d8');
      s += pill(loopX+10, (y1+yTop)/2, verb, '#8fa2d8');
      return;
    }
    const B=pos[b];
    const x1=A.x, y1=rowY(a,col);
    const x2=B.x+B.w, y2=B.y+HH+RH/2;
    const mx=(x1+x2)/2;
    s += `<path data-a="${a}" data-b="${b}" d="M${x1},${y1} C${mx},${y1} ${mx},${y2} ${x2},${y2}" fill="none" stroke="#394275" stroke-width="1.4"/>`;
    s += glyph(oto?'bar':'fork', x1-2, y1, 180, '#6ea8fe');
    const px=x2+2;
    s += glyph('bar', opt?px+11:px, y2, 0, '#6ea8fe');
    if(opt) s += glyph('ring', px, y2, 0, '#6ea8fe');
    s += pill((x1+x2)/2, (y1+y2)/2, verb, '#394275');
  });
  svg.innerHTML = s;
}
function relatedSet(name){
  const s=new Set([name]);
  RELS.forEach(([a,,b])=>{ if(a===name)s.add(b); if(b===name)s.add(a); });
  return s;
}
function hoverHighlight(name,on){
  if(focusedTable) return;
  const keep = relatedSet(name);
  document.querySelectorAll('.tbl').forEach(el=>{
    if(el.classList.contains('dim') && on) return;
    el.style.opacity = (on && !keep.has(el.id.slice(2))) ? '.15' : '';
  });
  svg.querySelectorAll('[data-a]').forEach(p=>{
    const hot = p.dataset.a===name || p.dataset.b===name;
    if(p.tagName==='path'){
      p.setAttribute('stroke', on&&hot ? '#6ea8fe' : (p.getAttribute('stroke-dasharray') ? '#4a5a86' : '#394275'));
      p.setAttribute('stroke-width', on&&hot ? '2.4' : '1.4');
    } else { p.style.opacity = on && !hot ? '.1' : '1'; }
  });
}

/* ============================== SEARCH + MODULE FILTER ============================== */
function stripHtml(s){ return s.replace(/<[^>]+>/g,''); }
function applyFilters(){
  const q = document.getElementById('q').value.toLowerCase().trim();
  document.querySelectorAll('.tbl').forEach(el=>{
    const name = el.id.slice(2);
    const t = byName[name];
    const modOk = activeModules.size===0 || activeModules.has(t.module);
    const hay = (t.name+' '+t.cols.map(c=>c.n+' '+c.t+' '+(c.note||'')).join(' ')+' '+t.rules.map(r=>r.join(' ')).join(' ')+' '+stripHtml(t.story)).toLowerCase();
    const qOk = !q || hay.includes(q);
    el.classList.toggle('dim', !(modOk && qOk));
    el.classList.toggle('match', !!q && qOk);
  });
}
document.getElementById('q').addEventListener('input', applyFilters);
document.getElementById('q').addEventListener('keydown', e=>{
  if(e.key==='Enter'){
    const first = [...document.querySelectorAll('.tbl.match')][0];
    if(first){ const name=first.id.slice(2); select(name); fitToNodes([name]); }
  }
});

/* ============================== PAN / ZOOM ============================== */
let view = {x:0,y:0,scale:1};
function applyView(){ canvas.style.transform = `translate(${view.x}px,${view.y}px) scale(${view.scale})`; drawMinimap(); }
function zoomBy(f, cx, cy){
  const rect = wrap.getBoundingClientRect();
  cx = cx ?? rect.width/2; cy = cy ?? rect.height/2;
  const newScale = Math.min(2.4, Math.max(.25, view.scale*f));
  const wx = (cx - view.x)/view.scale, wy = (cy - view.y)/view.scale;
  view.x = cx - wx*newScale; view.y = cy - wy*newScale; view.scale = newScale;
  applyView();
}
function resetView(){ fitToNodes(TABLES.map(t=>t.name)); }
function fitToView(){ fitToNodes(TABLES.map(t=>t.name)); }
function fitToNodes(names){
  const rect = wrap.getBoundingClientRect();
  const xs=[], ys=[];
  names.forEach(n=>{ const p=pos[n]; xs.push(p.x, p.x+p.w); ys.push(p.y, p.y+p.h); });
  const minX=Math.min(...xs)-40, maxXX=Math.max(...xs)+40, minY=Math.min(...ys)-40, maxYY=Math.max(...ys)+40;
  const w=maxXX-minX, h=maxYY-minY;
  const scale = Math.min(1.4, Math.max(.3, Math.min(rect.width/w, rect.height/h)));
  view.scale = scale;
  view.x = rect.width/2 - (minX+w/2)*scale;
  view.y = rect.height/2 - (minY+h/2)*scale;
  applyView();
}
wrap.addEventListener('wheel', e=>{
  e.preventDefault();
  const rect = wrap.getBoundingClientRect();
  zoomBy(e.deltaY<0?1.08:0.93, e.clientX-rect.left, e.clientY-rect.top);
},{passive:false});
let dragging=false, dragStart=null;
wrap.addEventListener('mousedown', e=>{
  if(e.target.closest('.tbl')) return;
  dragging=true; dragStart={x:e.clientX-view.x, y:e.clientY-view.y}; wrap.classList.add('grabbing');
});
window.addEventListener('mousemove', e=>{
  if(!dragging) return;
  view.x = e.clientX-dragStart.x; view.y = e.clientY-dragStart.y; applyView();
});
window.addEventListener('mouseup', ()=>{ dragging=false; wrap.classList.remove('grabbing'); });

/* ============================== MINIMAP ============================== */
const mmsvg = document.getElementById('mmsvg');
const MM_W=190, MM_H=130;
function drawMinimap(){
  const s = Math.min(MM_W/maxX, MM_H/maxY);
  const offX=(MM_W-maxX*s)/2, offY=(MM_H-maxY*s)/2;
  let inner = TABLES.map(t=>{
    const p=pos[t.name];
    return `<rect x="${offX+p.x*s}" y="${offY+p.y*s}" width="${p.w*s}" height="${p.h*s}" rx="1.5" class="mn"/>`;
  }).join('');
  const rect = wrap.getBoundingClientRect();
  const vx=offX + (-view.x/view.scale)*s, vy=offY + (-view.y/view.scale)*s;
  const vw=(rect.width/view.scale)*s, vh=(rect.height/view.scale)*s;
  inner += `<rect x="${vx}" y="${vy}" width="${vw}" height="${vh}" class="mv"/>`;
  mmsvg.setAttribute('viewBox', `0 0 ${MM_W} ${MM_H}`);
  mmsvg.innerHTML = inner;
}
document.getElementById('minimap').addEventListener('click', e=>{
  const rect = e.currentTarget.getBoundingClientRect();
  const s = Math.min(MM_W/maxX, MM_H/maxY);
  const offX=(MM_W-maxX*s)/2, offY=(MM_H-maxY*s)/2;
  const mx = (e.clientX-rect.left-offX)/s, my=(e.clientY-rect.top-offY)/s;
  const wrapRect = wrap.getBoundingClientRect();
  view.x = wrapRect.width/2 - mx*view.scale;
  view.y = wrapRect.height/2 - my*view.scale;
  applyView();
});
window.addEventListener('resize', drawMinimap);


function initGraph(){ drawEdges(); requestAnimationFrame(()=>{ fitToView(); }); }