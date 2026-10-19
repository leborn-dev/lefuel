import {test} from 'node:test';
import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
const {JSDOM}=await import(process.env.JSDOM_MODULE || 'jsdom');
function setup(){
 const dom=new JSDOM('<!doctype html><html><body></body></html>',{runScripts:'outside-only',url:'https://example.test/'});
 dom.window.eval(readFileSync(process.env.JQUERY_SOURCE,'utf8'));
 dom.window.eval(readFileSync(new URL('../public/assets/js/'+(process.env.TEST_BOOTSTRAP || 'bootstrap.min.js'),import.meta.url),'utf8'));
 return dom;
}
test('button loading data stays literal and reset retains the original markup',async()=>{
 const dom=setup(),$=dom.window.jQuery;
 const el=$('<button>Original <b>label</b></button>').attr('data-loading-text','<img src=x onerror="window.pwned=1">').appendTo('body');
 el.button('loading');await new Promise(r=>setTimeout(r,5));
 assert.equal(el.find('img').length,0);assert.ok(el.text().includes('<img'));
 el.button('reset');await new Promise(r=>setTimeout(r,5));assert.equal(el.html(),'Original <b>label</b>');dom.window.close();
});
test('popover sanitizes HTML despite a clobbered document implementation',()=>{
 const dom=setup(),w=dom.window,$=w.jQuery;let called=false;
 Object.defineProperty(w.document,'implementation',{configurable:true,value:{createHTMLDocument(){called=true;throw Error('clobbered');}}});
 const el=$('<button title="Title">Test</button>').appendTo('body');
 el.popover({html:true,content:'<img src=x onerror="window.pwned=1"><b>safe</b>'});const popover=el.data('bs.popover');popover.setContent();
 assert.equal(called,false);assert.equal(popover.tip().find('[onerror]').length,0);assert.equal(popover.tip().find('b').text(),'safe');dom.window.close();
});
test('the existing collapse plugin opens and closes normally',()=>{
 const dom=setup(),$=dom.window.jQuery,el=$('<div class="collapse">content</div>').appendTo('body');
 el.collapse({toggle:false});el.collapse('show');assert.ok(el.hasClass('in'));el.collapse('hide');assert.ok(!el.hasClass('in'));dom.window.close();
});
