(()=>{"use strict";var e={};function t(){return t=Object.assign?Object.assign.bind():function(e){for(var t=1;t<arguments.length;t++){var n=arguments[t];for(var l in n)Object.prototype.hasOwnProperty.call(n,l)&&(e[l]=n[l])}return e},t.apply(this,arguments)}e.n=t=>{var n=t&&t.__esModule?()=>t.default:()=>t;return e.d(n,{a:n}),n},e.d=(t,n)=>{for(var l in n)e.o(n,l)&&!e.o(t,l)&&Object.defineProperty(t,l,{enumerable:!0,get:n[l]})},e.o=(e,t)=>Object.prototype.hasOwnProperty.call(e,t);const n=window.wp.element,l=window.wp.compose,a=window.wp.hooks,r=window.wp.data,o=window.wp.apiFetch;var i=e.n(o);const c=window.wp.components,s=window.wp.blockEditor,u=window.wp.i18n,d=wubtitle_button_object.langExten,b={pending:(0,u.__)("Generating","wubtitle"),draft:(0,u.__)("Draft","wubtitle"),enabled:(0,u.__)("Enabled","wubtitle"),notfound:(0,u.__)("None","wubtitle")},p=Object.entries(d).map((e=>{let[t,n]=e;return{value:t,label:n}})),_=["it-IT","en-US"],m=Object.keys(d);Object.entries(d).map((e=>{let[t,n]=e;return _.includes(t)?{value:t,label:n}:{value:t,label:`${n} ${(0,u.__)("(Pro Only)","wubtitle")}`,disabled:!0}})),d.it=(0,u.__)("Italian","wubtitle"),d.en=(0,u.__)("English","wubtitle"),d.es=(0,u.__)("Spanish","wubtitle"),d.de=(0,u.__)("German","wubtitle"),d.zh=(0,u.__)("Chinese","wubtitle"),d.fr=(0,u.__)("French","wubtitle");const w=e=>{let{statusText:t,langText:l}=e;return(0,n.createElement)(n.Fragment,null,(0,n.createElement)("div",null,(0,u.__)("Status:","wubtitle")+" "+b[t]),(0,n.createElement)("div",null,(0,u.__)("Language:","wubtitle")+" "+d[l]))},g=e=>{let{statusText:t,langText:l,isPublished:a,postId:o}=e;const[i,s]=(0,n.useState)(""),p=(0,r.useDispatch)("core");return(0,n.createElement)(n.Fragment,null,(0,n.createElement)("p",{style:{margin:"0"}},(0,u.__)("Status:","wubtitle")+" "+b[t]),(0,n.createElement)("p",{style:{margin:"8px 0"}},(0,u.__)("Language:","wubtitle")+" "+d[l]),(0,n.createElement)(c.ToggleControl,{label:(0,u.__)("Published","wubtitle"),checked:a,onChange:()=>{(e=>{let t="draft";var n;(e=!e)&&(t="enabled"),n=t,p.editEntityRecord("postType","attachment",o,{meta:{wubtitle_status:n}}),p.saveEditedEntityRecord("postType","attachment",o)})(a)}}),(0,n.createElement)(c.Button,{name:"sottotitoli",id:o,isPrimary:!0,onClick:()=>{s((0,u.__)("Getting transcript…","wubtitle")),wp.ajax.send("get_transcript_internal_video",{type:"POST",data:{id:o,_ajax_nonce:wubtitle_button_object.ajaxnonce}}).then((e=>{s("Done");const t=wp.data.select("core/block-editor").getBlockIndex(wp.data.select("core/block-editor").getSelectedBlock().clientId),n=wp.blocks.createBlock("wubtitle/transcription",{contentId:e});wp.data.dispatch("core/block-editor").insertBlocks(n,t+1)})).fail((e=>{s(e)}))}},(0,u.__)("Get Transcribe","wubtitle")),(0,n.createElement)("p",null,i))},h=e=>{const t=void 0!==e.id?e.src.substring(e.src.lastIndexOf(".")+1):"mp4",l=("1"===wubtitle_button_object.isFree?_:m).includes(wubtitle_button_object.lang)?wubtitle_button_object.lang:"en-US",a=(0,r.useSelect)((t=>{let n;void 0!==e.id&&(n=t("core").getEntityRecord("postType","attachment",e.id));let l="";return void 0!==n&&(l=t("core").getEditedEntityRecord("postType","attachment",e.id).meta),l}));let o;void 0!==a&&(o=a.wubtitle_lang_video);const d=(0,r.useDispatch)("core/notices"),b=(0,r.useDispatch)("core"),[h,E]=(0,n.useState)(l),[v,f]=(0,n.useState)(!1),[y,S]=(0,n.useState)(null==a?void 0:a.wubtitle_status);(0,n.useEffect)((()=>{S(null==a?void 0:a.wubtitle_status)}),[null==a?void 0:a.wubtitle_status]);const k="pending"===y||void 0===e.id||v,T="enabled"===y,j=()=>{const t="error"===y?(0,u.__)("Error","wubtitle"):(0,u.__)("None","wubtitle");return(0,n.createElement)(n.Fragment,null,(0,n.createElement)("div",null,(0,u.__)("Status:","wubtitle")+" "+t),(0,n.createElement)(c.SelectControl,{label:(0,u.__)("Select the video language","wubtitle"),value:h,onChange:e=>{E(e)},options:p}),(0,n.createElement)(c.Button,{disabled:k,name:"sottotitoli",id:e.id,isPrimary:!0,onClick:I},(0,u.__)("GENERATE SUBTITLES","wubtitle")))},x=()=>(0,n.createElement)(n.Fragment,null,(0,n.createElement)("div",null,(0,u.__)("Unsupported video format for free plan","wubtitle")));function I(){const t=e.id,n=e.src;f(!0),i()({url:wubtitle_button_object.ajax_url,method:"POST",headers:{"Content-Type":"application/x-www-form-urlencoded; charset=utf-8"},body:`action=submitVideo&_ajax_nonce=${wubtitle_button_object.ajaxnonce}&id_attachment=${t}&src_attachment=${n}&lang=${h}&`}).then((t=>{f(!1),201===t.data?(d.createNotice("success",(0,u.__)("Subtitle creation successfully started","wubtitle")),b.editEntityRecord("postType","attachment",e.id,{meta:{wubtitle_status:"pending",wubtitle_lang_video:h}})):d.createNotice("error",t.data)}))}return(0,n.createElement)(s.InspectorControls,null,(0,n.createElement)(c.PanelBody,{title:"Wubtitle"},(0,n.createElement)((()=>{if("1"===wubtitle_button_object.isFree&&"mp4"!==t)return(0,n.createElement)(x,null);switch(y){case"pending":return(0,n.createElement)(w,{langText:o,statusText:y});case"draft":case"enabled":return(0,n.createElement)(g,{statusText:y,langText:o,isPublished:T,postId:e.id});default:return(0,n.createElement)(j,null)}}),{status:y,languageSaved:o})))},E=(0,l.createHigherOrderComponent)((e=>l=>"core/video"!==l.name?(0,n.createElement)(e,l):(0,n.createElement)(n.Fragment,null,(0,n.createElement)(e,l),(0,n.createElement)(h,t({},l.attributes,{setAttributes:l.setAttributes})))),"withInspectorControls");(0,a.addFilter)("editor.BlockEdit","wubtitle/with-inspector-controls",E);const v=e=>{const[t,l]=(0,n.useState)(""),[a,o]=(0,n.useState)((0,u.__)("None","wubtitle")),[i,b]=(0,n.useState)(""),[p,_]=(0,n.useState)(!1),[m,w]=(0,n.useState)([]),[g,h]=(0,n.useState)(""),[E,v]=(0,n.useState)(""),[f,y]=(0,n.useState)(!0),S=(0,r.useDispatch)("core/notices"),k=p||!E;E!==e.url&&(v(e.url),_(!1),l(""));const T=()=>{_(!0),w([]),wp.ajax.send("get_video_info",{type:"POST",data:{url:E,_ajax_nonce:wubtitle_button_object.ajaxnonce}}).then((e=>{if(!e.languages)return void l((0,u.__)("Subtitles not available for this video","wubtitle"));l("");const t=e.languages.map((t=>{if("youtube"===e.source)return{value:t.baseUrl,label:t.name.simpleText};let n=t.name;var l,a,r;return!n&&t.code.includes("autogen")&&(n=null!==(l=d[null===(a=t.code)||void 0===a||null===(r=a.split("-"))||void 0===r?void 0:r[0]])&&void 0!==l?l:""),{value:t.code,label:n}}));t.unshift({value:"none",label:(0,u.__)("Select language","wubtitle")}),w(t),h(e.title)})).fail((e=>{S.createNotice("error",e),l("")}))};return!p&&E&&"core-embed/youtube"===e.block&&T(),(0,n.createElement)(s.InspectorControls,null,(0,n.createElement)(c.PanelBody,{title:"Wubtitle"},(0,n.createElement)("p",{style:{margin:"0",marginBottom:"20px"}},`${(0,u.__)("Transcript status:","wubtitle")} ${a}`),"core-embed/vimeo"!==e.block||p?"":(0,n.createElement)(c.Button,{name:"",isPrimary:!0,onClick:T,disabled:k},(0,u.__)("Select transcript language","wubtitle")),E&&p?(0,n.createElement)(c.SelectControl,{label:(0,u.__)("Select the video language","wubtitle"),value:i,onChange:e=>{b(e),y("none"===e)},options:m}):"","core-embed/youtube"===e.block||p?(0,n.createElement)(c.Button,{name:"sottotitoli",id:e.id,isPrimary:!0,onClick:()=>{y(!0);const e=wp.data.select("core/block-editor").getBlockIndex(wp.data.select("core/block-editor").getSelectedBlock().clientId);l((0,u.__)("Getting transcript…","ear2words")),wp.ajax.send("get_transcript_embed",{type:"POST",data:{urlVideo:E,subtitle:i,videoTitle:g,from:"default_post_type",_ajax_nonce:wubtitle_button_object.ajaxnonce}}).then((t=>{y(!1);const n=wp.blocks.createBlock("wubtitle/transcription",{contentId:t}),a=e+1;wp.data.dispatch("core/block-editor").insertBlocks(n,a),l(""),o((0,u.__)("Created","wubtitle"))})).fail((e=>{y(!1),S.createNotice("error",e),l("")}))},disabled:f},(0,u.__)("Get Transcribe","wubtitle")):"",(0,n.createElement)("p",null,t)))},f=(0,l.createHigherOrderComponent)((e=>l=>{let a;if("core/embed"===l.name)switch(l.attributes.providerNameSlug){case"youtube":a="core-embed/youtube";break;case"vimeo":a="core-embed/vimeo";break;default:a=""}const r="core/embed"===l.name&&""!==a;return"core-embed/youtube"===l.name||"core-embed/vimeo"===l.name||r?(0,n.createElement)(n.Fragment,null,(0,n.createElement)(e,l),(0,n.createElement)(v,t({},l.attributes,{setAttributes:l.setAttributes,block:a||l.name}))):(0,n.createElement)(e,l)}),"withInspectorControls");(0,a.addFilter)("editor.BlockEdit","wubtitle/with-inspector-controls",f);(0,window.wp.blocks.registerBlockType)("wubtitle/transcription",{title:(0,u.__)("Trascription","wubtitle"),icon:"megaphone",description:(0,u.__)("Enter the transcript of your video","wubtitle"),category:"embed",attributes:{contentId:{type:"int"}},edit:e=>{let{attributes:t,setAttributes:l,className:a}=e;const[o,i]=(0,n.useState)(""),[s,d]=(0,n.useState)(""),[b,p]=(0,n.useState)([]),_=function(e){let t=arguments.length>1&&void 0!==arguments[1]?arguments[1]:100;const[l,a]=(0,n.useState)(e);return(0,n.useEffect)((()=>{const n=setTimeout((()=>{a(e)}),t);return()=>{clearTimeout(n)}}),[e]),l}(o,500),m=e=>e.replace(/&#(\d+);/g,((e,t)=>String.fromCharCode(t))),w=e=>{const t=wp.blocks.createBlock("core/paragraph",{content:e}),n=wp.data.select("core/block-editor").getSelectedBlock().clientId;wp.data.dispatch("core/block-editor").replaceBlocks(n,t),wp.data.dispatch("core/block-editor").clearSelectedBlock()};(0,n.useEffect)((()=>{d(_)}),[_]),(0,r.useSelect)((e=>{if(t.contentId&&0===b.length){const n={per_page:1,include:`${t.contentId}`},l=e("core").getEntityRecords("postType","transcript",n);if(null!==l){p([l[0].title.rendered]);let e=l[0].content.rendered;e=e.replace("<p>",""),e=e.replace("</p>",""),w(e)}}}));const g=(0,r.useSelect)((e=>{if(s.length>2){const t={per_page:10,search:s},n=e("core").getEntityRecords("postType","transcript",t);return null!==n?n:[]}return[]})),h=new Map,E=[];for(let e=0;e<g.length;e++)h.set(m(g[e].title.rendered),g[e].id),h.set(m(`${g[e].title.rendered} content`),g[e].content.rendered),E[e]=m(g[e].title.rendered);let v="";return(0,n.createElement)(n.Fragment,null,(0,n.createElement)(c.FormTokenField,{className:a,label:(0,u.__)("Wubtitle transcriptions","wubtitle"),value:b,suggestions:E,onChange:e=>(e=>{if(0===e.length)l({contentId:null}),p(e);else if(E.includes(e[0])){const t=h.get(e[0]),n=`${e[0]} content`;v=h.get(n),v=v.replace("<p>",""),v=v.replace("</p>",""),p(e),l({contentId:t}),w(v)}})(e),placeholder:(0,u.__)("Insert transcriptions","wubtitle"),onInputChange:e=>i(e),maxLength:1}),(0,n.createElement)("p",{className:"helperText"},(0,u.__)("Enter the title of the video you want to transcribe","wubtitle")))}})})();