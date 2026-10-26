import fs from 'node:fs';
import path from 'node:path';
import {fileURLToPath} from 'node:url';
// Use the existing project runtime. Override PHP_WASM_ROOT when moving this package.
const root=process.env.PHP_WASM_ROOT;
if(!root)throw new Error('Set PHP_WASM_ROOT to an existing node_modules with @php-wasm/node and @php-wasm/universal, or run php test-graph.php directly.');
const {PHP}=await import(root+'/@php-wasm/universal/index.js');
const {loadNodeRuntime}=await import(root+'/@php-wasm/node/index.js');
const base=path.dirname(fileURLToPath(import.meta.url));
const results=[];
for(const version of ['8.0','8.3']) {
 const php=new PHP(await loadNodeRuntime(version,{emscriptenOptions:{processId:version==='8.0'?201:202}}));
 php.mkdir('/work');php.mkdir('/work/fixtures');
 for(const f of ['editorial-author.php','test-graph.php','fixtures/graphs17.json'])php.writeFile('/work/'+f,fs.readFileSync(path.join(base,f)));
 const lint=await php.run({code:"<?php token_get_all(file_get_contents('/work/editorial-author.php'),TOKEN_PARSE); echo 'PASS';"});
 const run=await php.run({scriptPath:'/work/test-graph.php'});
 let data;try{data=JSON.parse(run.text);}catch(e){throw new Error(run.text+' '+run.errors);}
 results.push({php:version,lint:lint.text,exitCode:run.exitCode,stderr:run.errors,...data});
 php.exit();
}
fs.writeFileSync(path.join(base,'test-results.json'),JSON.stringify(results,null,2));
console.log(results.map(x=>({php:x.php,lint:x.lint,total:x.total,failed:x.failed,exitCode:x.exitCode})));
if(results.some(x=>x.failed||x.lint!=='PASS'||x.exitCode))process.exit(1);
