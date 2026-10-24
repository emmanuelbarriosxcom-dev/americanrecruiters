import fs from 'node:fs';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime,createNodeFsMountHandler} from '@php-wasm/node';

export async function exclusiveRace({root,plugin,version}) {
  const instances=[];
  for(let i=0;i<6;i++){
    const p=new PHP(await loadNodeRuntime(version,{emscriptenOptions:{processId:20+i}}));
    p.mkdirTree(root);await p.mount(root,createNodeFsMountHandler(root));
    p.mkdirTree('/plugin');await p.mount('/plugin',createNodeFsMountHandler(plugin));instances.push(p);
  }
  const id='e'.repeat(64);const store=root+'/atomic-race';
  await instances[0].run({code:`<?php require '/plugin/src/PrivateStore.php';$s=new \\ARV\\PrivateStore('${store}',[]);$s->initialize();`});
  const rows=await Promise.all(instances.map(async(p,i)=>{
    const r=await p.run({code:`<?php require '/plugin/src/PrivateStore.php';$s=new \\ARV\\PrivateStore('${store}',[]);$s->initialize();$winner=$s->claim('${id}','same-fingerprint',time()+3600);if($winner){file_put_contents('${root}/race-effect.txt','one simulated transport effect');chmod('${root}/race-effect.txt',0600);}echo json_encode(['worker'=>${i},'claimed'=>$winner]);`});
    return JSON.parse(r.text);
  }));
  const rateRows=await Promise.all(instances.map(async(p,i)=>{
    const r=await p.run({code:`<?php require '/plugin/src/PrivateStore.php';$s=new \\ARV\\PrivateStore('${store}',[]);$s->initialize();echo json_encode(['worker'=>${i},'allowed'=>$s->rate(str_repeat('a',64),900000,3,600)]);`});return JSON.parse(r.text);
  }));
  instances.forEach(p=>p.exit());
  const result={version,independentPhpInstances:6,rows,winners:rows.filter(r=>r.claimed).length,rateRows,rateWinners:rateRows.filter(r=>r.allowed).length,effect:fs.readFileSync(root+'/race-effect.txt','utf8')};
  fs.writeFileSync(root+'/evidence/atomic-race-'+version+'.json',JSON.stringify(result,null,2));
  return result;
}
