<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>CE Builder (Drag & Drop + Code)</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>

<style>
:root{
  --ce-bg:#f3f4f6;--ce-white:#fff;--ce-border:#e5e7eb;--ce-text:#111827;--ce-muted:#6b7280;
  --ce-accent:#6366f1;--ce-accent-light:rgba(99,102,241,.08);
  --ce-radius:6px;--ce-shadow-sm:0 1px 3px rgba(0,0,0,.08);--ce-shadow-md:0 6px 18px rgba(0,0,0,.15);
  --ce-transition:.18s ease;font-family:Inter,system-ui,sans-serif;
}
body{margin:0;background:var(--ce-bg);color:var(--ce-text);}

/* TABS */
.ce-tabs{display:flex;background:var(--ce-white);border-bottom:1px solid var(--ce-border);}
.ce-tab-btn{padding:10px 16px;border:none;background:none;cursor:pointer;font-size:14px;border-right:1px solid var(--ce-border);
  color:var(--ce-text);display:flex;gap:6px;align-items:center;transition:background var(--ce-transition);}
.ce-tab-btn:hover{background:#f9fafb;}
.ce-tab-btn.ce-active{background:var(--ce-accent-light);color:var(--ce-accent);font-weight:600;}
.ce-tab-btn.ce-action{border-right:none;margin-left:auto;}
.ce-tab-pane{display:none;height:calc(100vh - 41px);}
.ce-tab-pane.ce-active{display:flex;}

/* LAYOUT */
.ce-editor { display:flex; max-height:100vh; width:100%; overflow:auto; }
.ce-inspector{width:300px;background:var(--ce-white);border-right:1px solid var(--ce-border);display:flex;flex-direction:column;}
.ce-panel-header{padding:14px 16px;font-weight:600;border-bottom:1px solid var(--ce-border);background:#f9fafb;}
.ce-inspector-actions{padding:8px 12px;border-bottom:1px solid var(--ce-border);display:flex;gap:8px;}
.ce-inspector-body{flex:1;overflow:auto;padding:0;font-size:14px;display:flex;flex-direction:column;}
.ce-muted{color:var(--ce-muted);font-size:13px;padding:12px;}
.ce-inspector, .ce-components-panel { position: sticky; top: 41px; height: calc(100vh - 41px); overflow: auto; }

/* Inspector inner tabs */
.ce-prop-tabs{display:flex;border-bottom:1px solid var(--ce-border);}
.ce-prop-tab-btn{flex:1;border:none;background:none;padding:10px 0;font-size:13px;cursor:pointer;color:var(--ce-text);transition:background var(--ce-transition);}
.ce-prop-tab-btn:hover{background:#f3f4f6;}
.ce-prop-tab-btn.ce-active{background:var(--ce-accent-light);color:var(--ce-accent);font-weight:600;}
.ce-prop-pane{display:none;padding:12px;overflow:auto;flex:1;}
.ce-prop-pane.ce-active{display:block;}

/* FIELDS */
.ce-field{margin:0 0 10px 0;}
.ce-field label{display:block;font-size:12px;margin:0 0 4px 0;color:#374151;font-weight:500;}
.ce-field input[type="text"],.ce-field input[type="number"],.ce-field input[type="color"],.ce-field textarea,.ce-field select{
  width:100%;border:1px solid var(--ce-border);border-radius:4px;padding:6px 8px;font-size:13px;box-sizing:border-box;background:#fff;}
.ce-field textarea{min-height:90px;resize:vertical;}
.ce-color-pair{display:flex;gap:8px;}
.ce-color-pair input[type=color]{width:34px;height:34px;padding:0;border:none;border-radius:4px;cursor:pointer;}
.ce-align-group,.ce-typo-tools,.ce-img-align{display:flex;gap:6px;margin-top:4px;}
.ce-align-btn,.ce-typo-btn,.ce-img-align-btn{
  flex:1;border:1px solid var(--ce-border);background:#fff;border-radius:4px;padding:6px 0;cursor:pointer;font-size:14px;
  display:flex;align-items:center;justify-content:center;color:#374151;transition:background .12s;}
.ce-align-btn.active,.ce-typo-btn.active,.ce-img-align-btn.active{
  background:var(--ce-accent-light);color:var(--ce-accent);border-color:var(--ce-accent);
}
.ce-unit-toggle {display: flex; gap: 6px; margin-bottom: 8px;}
.ce-unit-toggle button {
  flex: 1; padding: 4px; border: 1px solid var(--ce-border); background: #fff;
  border-radius: 4px; cursor: pointer; font-size: 12px;
}
.ce-unit-toggle button.active {
  background: var(--ce-accent-light); color: var(--ce-accent); border-color: var(--ce-accent);
}

/* CANVAS */
.ce-canvas-wrap{flex:1;position:relative;overflow:auto;background:#f3f4f6;max-height: 100vh}
.ce-canvas{
  min-height:100%;padding:32px 24px;outline:none;position:relative;
  width: 100%; max-width: 600px; margin: 24px auto; background: var(--ce-white); box-shadow: var(--ce-shadow-sm); border-radius: 8px;
}
.ce-canvas[data-placeholder]:empty:before{
  content:attr(data-placeholder);color:var(--ce-muted);font-size:14px;position:absolute;
  top:40%;left:50%;transform:translate(-50%,-50%);text-align:center;width:240px;
}

/* COMPONENTS */
.ce-components-panel{width:300px;background:var(--ce-white);border-left:1px solid var(--ce-border);display:flex;flex-direction:column;}
.ce-comp-tabs{display:flex;border-bottom:1px solid var(--ce-border);}
.ce-comp-tab-btn{flex:1;padding:10px 0;text-align:center;background:none;border:none;cursor:pointer;font-size:13px;color:var(--ce-text);transition:background var(--ce-transition);}
.ce-comp-tab-btn:hover{background:#f3f4f6;}
.ce-comp-tab-btn.ce-active{background:var(--ce-accent-light);color:var(--ce-accent);font-weight:600;}
.ce-components-list{flex:1;overflow:auto;padding:12px;display:none;}
.ce-components-list.ce-active{display:block;}
.ce-component{
  border:1px solid var(--ce-border);border-radius:var(--ce-radius);background:var(--ce-white);
  padding:10px;margin-bottom:10px;cursor:grab;display:flex;align-items:center;gap:8px;font-size:14px;
  transition:background var(--ce-transition),transform .08s;}
.ce-component:hover{background:#f3f4f6;}
.ce-component i{font-size:16px;}

/* BLOCK (only edit canvas shows outlines/handles) */
#ceCanvasEdit .ce-block{position:relative;}
#ceCanvasEdit .ce-block:hover::after,#ceCanvasEdit .ce-block.ce-selected::after{
  content:"";position:absolute;inset:0;outline:1px dotted var(--ce-accent);pointer-events:none;z-index:2;
}
.ce-block-handle{
  position:absolute;right:6px;top:6px;background:var(--ce-accent);color:#fff;border-radius:3px;
  padding:2px 6px;font-size:11px;display:flex;gap:6px;align-items:center;box-shadow:var(--ce-shadow-sm);
  opacity:0;pointer-events:none;transition:opacity .12s;z-index:3;
}
#ceCanvasEdit .ce-block:hover .ce-block-handle{opacity:1;pointer-events:auto;}
.ce-block-handle span{cursor:pointer;line-height:1;}
.ce-block-handle .ce-remove{color:#f87171;}
.ce-block-handle .ce-dup{color:#34d399;}
.ce-block-handle .ce-up,.ce-block-handle .ce-down{color:#fff;}

.ce-slot{min-height:24px;}
.ce-add-inside{
  margin-top:12px;display:flex;align-items:center;justify-content:center;gap:6px;
  font-size:12px;color:var(--ce-accent);cursor:pointer;width:100%;
}

/* ADD POPUP */
.ce-add-popup{
  position:absolute;background:#fff;border:1px solid var(--ce-border);border-radius:6px;
  box-shadow:var(--ce-shadow-md);padding:12px;width:220px;z-index:9999;font-size:13px;
}
.ce-add-popup h4{margin:0 0 8px 0;font-size:12px;color:#374151;font-weight:600;}
.ce-add-popup button{
  display:block;width:100%;text-align:left;border:0;background:none;padding:6px 8px;border-radius:4px;cursor:pointer;
}
.ce-add-popup button:hover{background:#f3f4f6;color:var(--ce-accent);}

/* DROP MARKER */
.ce-drop-marker{height:4px;background:var(--ce-accent);margin:4px 0;border-radius:2px;opacity:0;transition:opacity .1s linear;}
.ce-drop-marker.active{opacity:1;}

/* SMALL BUTTONS */
.ce-btn-sm{padding:6px 12px;border-radius:4px;border:1px solid var(--ce-border);background:#fff;cursor:pointer;font-size:13px;display:inline-flex;align-items:center;gap:4px;}
.ce-btn-sm.ce-primary{background:var(--ce-accent);border-color:var(--ce-accent);color:#fff;}

/* CODE TAB */
.ce-code-pane{flex-direction:column;}
.ce-code-wrap{flex:1;display:flex;}
.ce-code-left{flex:1;overflow:auto;background:#f9fafb;padding:16px;}
.ce-code-right{flex:1;display:flex;padding:16px;background:#fafafa;}
#ceCodeArea{
  width:100%;height:100%;border:1px solid var(--ce-border);border-radius:6px;padding:12px;font-family:monospace;font-size:13px;resize:none;
  background:var(--ce-white);color:#374151;
}
#ceCanvasEdit a {
  pointer-events: none;
  cursor: default;
}
.ce-code-actions{padding:10px 16px;background:var(--ce-white);border-top:1px solid var(--ce-border);text-align:right;}

/* MODAL */
#ceModal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:999;align-items:center;justify-content:center;}
#ceModal .ce-modal-box{
  background:#fff;padding:16px 20px;border-radius:8px;width:500px;max-height:80%;overflow:auto;box-shadow:var(--ce-shadow-md);
}
#ceExport{width:100%;height:300px;font-size:12px;}

/* Hide export canvas */
#ceCanvasExport{display:none;}

/* Device Preview */
.ce-device-preview {
  display: flex; justify-content: center; gap: 12px; padding: 12px; 
  border-bottom: 1px solid var(--ce-border); background: var(--ce-white);
  position: sticky;
  top: 0;
  z-index: 10;
}
.ce-device-btn {
  border: none; background: none; padding: 6px 12px; border-radius: 4px;
  cursor: pointer; font-size: 13px; display: flex; align-items: center; gap: 4px;
}
.ce-device-btn.active {
  background: var(--ce-accent-light); color: var(--ce-accent);
}
.ce-device-btn i { font-size: 16px; }

/* Responsive canvas sizes */
.ce-canvas.desktop { max-width: 768px; }
.ce-canvas.tablet { max-width: 768px; }
.ce-canvas.mobile { max-width: 375px; }

.ce-section-slot-wrapper {
  display: flex;
  flex-wrap: wrap;
  /* flex-direction: column !important; */
}
.ce-canvas.mobile .ce-section-slot-wrapper{
    display: flex;
  flex-wrap: wrap;
  flex-direction: column !important;
}
/* add this to your <style> */
    .ce-text-toolbar {
  margin-bottom: 6px;
}
.ce-text-toolbar button,
.ce-text-toolbar select,
.ce-text-toolbar input[type="color"] {
  margin-right: 4px;
  padding: 2px 6px;
  font-size: 14px;
  border: 1px solid var(--ce-border);
  background: #fff;
  border-radius: 4px;
  cursor: pointer;
}
.ce-text-area {
  min-height: 80px;
  border: 1px solid var(--ce-border);
  border-radius: 4px;
  padding: 8px;
  outline: none;
}

/* add this to your stylesheet */
.ce-text-toolbar {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5em;
  align-items: center;
}
.ce-text-toolbar .ce-font-controls {
  display: flex;
  gap: 0.25em;
}
.ce-text-toolbar select {
  padding: 0.2em 0.4em;
}
.ce-text-toolbar input[type="color"] {
  width: 1.5em;
  height: 1.5em;
  padding: 0;
  border: none;
  border-radius: 0;
}
.ce-text-area {
  border: 1px solid var(--ce-border);
  padding: 6px;
  min-height: 200px;
  margin-top: 0.5em;
  border-radius: 4px;
  outline: none;
}




</style>
</head>
<body>

<!-- TABS -->
<div class="ce-tabs" id="topTabs">
  <button class="ce-tab-btn ce-active" data-tab="editor"><i class="fa-solid fa-pen-ruler"></i>Editor</button>
  <button class="ce-tab-btn" data-tab="code"><i class="fa-solid fa-code"></i>Code</button>
  <button class="ce-tab-btn" data-tab="media"><i class="fa-solid fa-photo-film"></i>Media</button>
  <button class="ce-tab-btn ce-action" id="ceSave"><i class="fa-solid fa-file-export"></i>Export HTML</button>
</div>

<!-- EDITOR TAB -->
<div class="ce-tab-pane ce-active" id="tab-editor">
  <div class="ce-editor">
    <aside class="ce-inspector">
      <div class="ce-panel-header">Properties</div>
      <div class="ce-inspector-actions">
        <button id="ceUndo" class="ce-btn-sm" title="Undo (Ctrl+Z)"><i class="fa-solid fa-rotate-left"></i></button>
        <button id="ceRedo" class="ce-btn-sm" title="Redo (Ctrl+Shift+Z)"><i class="fa-solid fa-rotate-right"></i></button>
      </div>
      <div class="ce-inspector-body" id="ceInspector">
        <p class="ce-muted">Select a block to edit.</p>
      </div>
    </aside>

    <main class="ce-canvas-wrap">
      <div class="ce-device-preview">
        <button class="ce-device-btn active" data-device="desktop"><i class="fa-solid fa-desktop"></i> Desktop</button>
        <button class="ce-device-btn" data-device="tablet"><i class="fa-solid fa-tablet"></i> Tablet</button>
        <button class="ce-device-btn" data-device="mobile"><i class="fa-solid fa-mobile"></i> Mobile</button>
      </div>
      <!-- EDITING CANVAS -->
      <div id="ceCanvasEdit" class="ce-canvas desktop" data-placeholder="Drag components here"></div>
      <!-- CLEAN EXPORT MIRROR -->
      <div id="ceCanvasExport" class="ce-canvas"></div>
      <textarea id="ceDraftExport" style="display:none;"></textarea>

    </main>

    <aside class="ce-components-panel">
      <div class="ce-panel-header">Components</div>

      <div class="ce-comp-tabs">
        <button class="ce-comp-tab-btn ce-active" data-list="elements">Elements</button>
        <button class="ce-comp-tab-btn" data-list="sections">Sections</button>
      </div>

      <div class="ce-components-list ce-active" id="list-elements">
        <div class="ce-component" style="max-width:768px;" draggable="true" data-key="ce-personalized-greeting" data-html="<p style='margin:0 0 12px 0; line-height:1.5; font-size:1rem;'>Hi %%first_name%%,</p>"><i class="fa-solid fa-hand-wave"></i> Personalized Greeting</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-heading"   data-html="<h2 style='margin:0 0 12px 0;'>Your Heading</h2>"><i class="fa-solid fa-heading"></i> Heading</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-paragraph" data-html="<p style='margin:0 0 12px 0;line-height:1.5;'>Your paragraph text goes here…</p>"><i class="fa-solid fa-font"></i> Paragraph</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-ul"        data-html="<ul style='margin:0 0 12px 18px;padding:0;'><li>List item 1</li><li>List item 2</li></ul>"><i class="fa-solid fa-list-ul"></i> Unordered List</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-ol"        data-html="<ol style='margin:0 0 12px 18px;padding:0;'><li>List item 1</li><li>List item 2</li></ol>"><i class="fa-solid fa-list-ol"></i> Ordered List</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-image"     data-html="<img src='https://placehold.co/600x200' alt='image' style='max-width:100%;height:auto;display:inline-block;margin:0 0 12px 0;'/>"><i class="fa-solid fa-image"></i> Image</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-button"    data-html="<a href='#' style='display:inline-block;padding:10px 18px;background:#6366f1;color:#fff;border:1px solid #6366f1;border-radius:4px;text-decoration:none;margin:0 0 12px 0;'>Click me</a>"><i class="fa-solid fa-link"></i> Button</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-divider"   data-html="<hr style='border:0;border-top:2px solid #e5e7eb;margin:16px 0;'/>"><i class="fa-solid fa-minus"></i> Divider</div>
        <div class="ce-component" style='max-width:768px;' draggable="true" data-key="ce-html"      data-html="<div style='margin:0 0 12px 0;'>Custom HTML here</div>"><i class="fa-solid fa-code"></i> Custom HTML</div>
        <div class="ce-component" draggable="true" data-key="ce-social-links" data-html="<div class='cs-social-links' style='text-align:center;margin:12px 0;'><a href='https://facebook.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#1877F2' xmlns='http://www.w3.org/2000/svg'><path d='M22 12.07C22 6.49 17.52 2 12 2S2 6.49 2 12.07c0 5.01 3.66 9.16 8.44 9.93v-7.03H7.9V12h2.54V9.8c0-2.5 1.49-3.88 3.77-3.88 1.09 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.77-1.63 1.56V12h2.78l-.44 2.97h-2.34V22c4.78-.77 8.44-4.92 8.44-9.93z'/></svg></a><a href='https://twitter.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#1DA1F2' xmlns='http://www.w3.org/2000/svg'><path d='M7.55 3h3.02l3.64 5.18L17.96 3h2.99l-5.2 7.3L21 21h-3.02l-4.1-5.83L9.5 21H6.5l5.5-7.71L7.55 3z'/></svg></a><a href='https://instagram.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#E1306C' xmlns='http://www.w3.org/2000/svg'><path d='M7 2h10a5 5 0 0 1 5 5v10a5 5 0 0 1-5 5H7a5 5 0 0 1-5-5V7a5 5 0 0 1 5-5zm0 2a3 3 0 0 0-3 3v10a3 3 0 0 0 3 3h10a3 3 0 0 0 3-3V7a3 3 0 0 0-3-3H7zm5 3.5a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11zm0 2a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7zM18 6.5a1 1 0 1 1 0 2 1 1 0 0 1 0-2z'/></svg></a><a href='https://linkedin.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#0A66C2' xmlns='http://www.w3.org/2000/svg'><path d='M4.98 3.5C4.98 4.6 4.1 5.5 3 5.5S1 4.6 1 3.5 1.9 1.5 3 1.5s1.98.9 1.98 2zm.02 4H1v15h4V7.5zM8 7.5h3.8v2h.05c.53-1 1.84-2.05 3.78-2.05 4.05 0 4.8 2.5 4.8 5.8V22h-4v-6.6c0-1.57-.03-3.6-2.2-3.6-2.2 0-2.54 1.72-2.54 3.5V22H8V7.5z'/></svg></a><a href='https://youtube.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#FF0000' xmlns='http://www.w3.org/2000/svg'><path d='M23.5 6.2a2.83 2.83 0 0 0-1.98-2c-1.75-.47-8.77-.47-8.77-.47s-7.02 0-8.77.47A2.83 2.83 0 0 0 2 6.2 29.8 29.8 0 0 0 1.53 12 29.8 29.8 0 0 0 2 17.8a2.83 2.83 0 0 0 1.98 2c1.75.47 8.77.47 8.77.47s7.02 0 8.77-.47a2.83 2.83 0 0 0 1.98-2A29.8 29.8 0 0 0 24 12a29.8 29.8 0 0 0-.5-5.8zM9.75 15.02V8.98l5.5 3.02-5.5 3.02z'/></svg></a><a href='https://pinterest.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#BD081C' xmlns='http://www.w3.org/2000/svg'><path d='M12.04 2a10 10 0 0 0-3.59 19.34c-.05-.82-.1-2.07.02-2.97.11-.78.72-4.97.72-4.97s-.18-.36-.18-.89c0-.84.49-1.46 1.09-1.46.52 0 .77.39.77.86 0 .53-.34 1.31-.52 2.04-.15.65.32 1.18.94 1.18 1.13 0 2-1.19 2-2.91 0-1.52-1.1-2.59-2.68-2.59-1.82 0-2.89 1.36-2.89 2.76 0 .55.21 1.14.48 1.46.05.06.05.1.04.15-.05.17-.17.53-.2.61-.03.1-.1.13-.23.08-.85-.39-1.38-1.6-1.38-2.58 0-2.1 1.53-4.03 4.41-4.03 2.31 0 4.11 1.65 4.11 3.86 0 2.29-1.44 4.14-3.43 4.14-.67 0-1.29-.35-1.51-.77l-.41 1.54c-.15.57-.56 1.29-.83 1.73A10 10 0 1 0 12.04 2z'/></svg></a><a href='https://wa.me/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#25D366' xmlns='http://www.w3.org/2000/svg'><path d='M12.04 2A9.94 9.94 0 0 0 2 12c0 1.76.46 3.43 1.27 4.87L2 22l5.27-1.24A9.94 9.94 0 0 0 12.04 22a10 10 0 0 0 0-20zm0 18c-1.58 0-3.05-.41-4.34-1.13l-.31-.18-3.13.74.66-3.05-.2-.31A8.01 8.01 0 1 1 12.04 20zm4.5-5.89c-.25-.13-1.47-.73-1.7-.82-.23-.08-.4-.12-.57.13-.17.25-.65.82-.8.99-.15.17-.3.2-.55.07-.25-.13-1.06-.39-2.02-1.25-.74-.66-1.25-1.48-1.4-1.73-.15-.25-.02-.39.11-.52.12-.12.25-.3.37-.45.12-.15.16-.25.25-.42.08-.17.04-.32-.02-.45-.06-.12-.57-1.37-.78-1.87-.21-.5-.42-.43-.57-.44h-.48c-.17 0-.45.07-.69.33-.23.25-.91.88-.91 2.15s.93 2.49 1.06 2.66c.13.17 1.83 2.78 4.45 3.89.62.27 1.1.43 1.47.55.62.2 1.18.17 1.62.1.5-.08 1.47-.6 1.68-1.18.21-.58.21-1.07.15-1.18-.06-.12-.23-.18-.48-.31z'/></svg></a><a href='https://github.com/' target='_blank' style='display:inline-block;margin:0 6px;'><svg width='22' height='22' viewBox='0 0 24 24' fill='#181717' xmlns='http://www.w3.org/2000/svg'><path d='M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.38 7.86 10.9.58.1.79-.25.79-.56 0-.28-.01-1.04-.02-2.05-3.2.7-3.88-1.54-3.88-1.54-.53-1.34-1.3-1.7-1.3-1.7-1.06-.72.08-.71.08-.71 1.17.08 1.78 1.21 1.78 1.21 1.04 1.77 2.73 1.26 3.4.96.1-.75.41-1.26.74-1.55-2.55-.29-5.23-1.28-5.23-5.69 0-1.26.45-2.3 1.2-3.11-.12-.3-.52-1.5.11-3.13 0 0 .97-.31 3.18 1.19a11.1 11.1 0 0 1 5.8 0c2.2-1.5 3.17-1.19 3.17-1.19.63 1.63.23 2.83.11 3.13.75.81 1.2 1.85 1.2 3.11 0 4.42-2.69 5.4-5.25 5.69.42.36.79 1.07.79 2.16 0 1.56-.01 2.82-.01 3.2 0 .31.21.67.8.56A10.51 10.51 0 0 0 23.5 12c0-6.27-5.23-11.5-11.46-11.5z'/></svg></a></div>"><i class="fab fa-share-alt"></i> Social Links</div>
        <div class="ce-component" draggable="true" data-key="ce-footer" data-html="<div style='padding:12px;background:#f3f4f6;font-size:12px;text-align:center;color:#666;'>&copy; 2025 Your Company · <a href='#' style='color:inherit;text-decoration:underline;'>Unsubscribe</a></div>"><i class="fa-solid fa-flag"></i> Footer</div>
      </div>

    <div class="ce-components-list" id="list-sections">
        <div class="ce-component" draggable="true" data-key="ce-section-1" data-html="<section style='max-width:768px;margin:0 auto;padding:0;'><div class='ce-section-slot-wrapper' style='width:100%!important;max-width:768px;display:flex;justify-content:space-between;flex-wrap:wrap;'><div class='ce-section-slot' style='flex:1;min-width:100%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div></div></section>"><i class="fa-solid fa-square"></i> 1 Column</div>
        <div class="ce-component" draggable="true" data-key="ce-section-2" data-html="<section style='max-width:768px;margin:0 auto;padding:0;'><div class='ce-section-slot-wrapper' style='width:100%!important;max-width:768px;display:flex;justify-content:space-between;flex-wrap:wrap;'><div class='ce-section-slot' style='flex:1;min-width:49%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div><div class='ce-section-slot' style='flex:1;min-width:49%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div></div></section>"><i class="fa-solid fa-table-columns"></i> 2 Columns</div>
        <div class="ce-component" draggable="true" data-key="ce-section-3" data-html="<section style='max-width:768px;margin:0 auto;padding:0;'><div class='ce-section-slot-wrapper' style='width:100%!important;max-width:768px;display:flex;justify-content:space-between;flex-wrap:wrap;'><div class='ce-section-slot' style='flex:1;min-width:32%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div><div class='ce-section-slot' style='flex:1;min-width:32%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div><div class='ce-section-slot' style='flex:1;min-width:32%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div></div></section>"><i class="fa-solid fa-border-all"></i> 3 Columns</div>
        <div class="ce-component" draggable="true" data-key="ce-section-4" data-html="<section style='max-width:768px;margin:0 auto;padding:0;'><div class='ce-section-slot-wrapper' style='width:100%!important;max-width:768px;display:flex;justify-content:space-between;flex-wrap:wrap;'><div class='ce-section-slot' style='flex:1;min-width:23%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div><div class='ce-section-slot' style='flex:1;min-width:23%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div><div class='ce-section-slot' style='flex:1;min-width:23%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div><div class='ce-section-slot' style='flex:1;min-width:23%;position:relative;display:flex;flex-direction:column;'><div class='ce-slot'></div><span class='ce-add-inside'><i class='fa-solid fa-plus'></i> Add content</span></div></div></section>"><i class="fa-solid fa-grip-lines"></i> 4 Columns</div>
      </div>
      
    </aside>
  </div>
</div>

<!-- CODE TAB -->
<div class="ce-tab-pane ce-code-pane" id="tab-code">
  <div class="ce-code-wrap">
    <div class="ce-code-left">
      <div class="ce-panel-header" style="border:none;background:transparent;padding:0 0 8px 0;">Preview</div>
      <iframe id="ceCodePreview" style="width:100%;height:100%;border:1px solid var(--ce-border);border-radius:6px;background:#fff;"></iframe>
    </div>
    <div class="ce-code-right">
      <textarea id="ceCodeArea"></textarea>
    </div>
  </div>
  <div class="ce-code-actions">
    <button type="button" class="ce-btn-sm" id="ceCodeRefresh"><i class="fa-solid fa-rotate-right"></i> Refresh</button>
    <button type="button" class="ce-btn-sm ce-primary" id="ceCodeApply"><i class="fa-solid fa-check"></i> Apply</button>
  </div>
</div>

<!-- MEDIA TAB -->
<div class="ce-tab-pane" id="tab-media" style="background:#fff;overflow:auto;">
  @include('modules.media.manageMedia')
</div>

<!-- EXPORT MODAL -->
<div id="ceModal">
  <div class="ce-modal-box">
    <h3 style="margin-top:0;margin-bottom:12px;font-size:18px;">Exported HTML</h3>
    <textarea id="ceExport"></textarea>
    <div style="text-align:right;margin-top:10px;">
      <button id="ceCloseModal" class="ce-btn-sm"><i class="fa-solid fa-xmark"></i> Close</button>
    </div>
  </div>
</div>

<script>
    // holds the original import’s doctype (e.g. <!DOCTYPE html>)
let importedDocDoctype = '';
// holds everything inside the original <head>…</head>
let importedDocHead = '';
(function(){
  const state = {
    editEl:null,
    outEl:null,
    markerEl:null,
    selectedBlock:null,
    undoStack:[],
    redoStack:[],
    saving:false,
    currentUnit: 'px', // 'px' or '%'
    currentDevice: 'desktop' // 'desktop', 'tablet', 'mobile'
  };

  /* ===== HISTORY ===== */
  function pushHistory(){
    if(state.saving) return;
    state.undoStack.push(state.editEl.innerHTML);
    if(state.undoStack.length>100) state.undoStack.shift();
    state.redoStack.length=0;
  }
  function undo(){
    if(!state.undoStack.length) return;
    state.redoStack.push(state.editEl.innerHTML);
    const last=state.undoStack.pop();
    state.editEl.innerHTML=last;
    rebindAll();
    syncExport();
  }
  function redo(){
    if(!state.redoStack.length) return;
    state.undoStack.push(state.editEl.innerHTML);
    const last=state.redoStack.pop();
    state.editEl.innerHTML=last;
    rebindAll();
    syncExport();
  }

  /* ===== TABS ===== */
  document.getElementById('topTabs').addEventListener('click', (e)=>{
    const btn=e.target.closest('.ce-tab-btn');
    if(!btn) return;
    if(btn.id==='ceSave'){ exportHTML(); return; }
    const tab=btn.dataset.tab;
    document.querySelectorAll('.ce-tab-btn[data-tab]').forEach(b=>b.classList.remove('ce-active'));
    btn.classList.add('ce-active');
    document.querySelectorAll('.ce-tab-pane').forEach(p=>p.classList.remove('ce-active'));
    document.getElementById('tab-'+tab).classList.add('ce-active');
    if(tab==='code'){ loadCodeFromExport(); }
  });

  /* COMPONENT TABS */
  document.querySelectorAll('.ce-comp-tab-btn').forEach(btn=>{
    btn.addEventListener('click',()=>{
      document.querySelectorAll('.ce-comp-tab-btn').forEach(b=>b.classList.remove('ce-active'));
      btn.classList.add('ce-active');
      const list=btn.dataset.list;
      document.querySelectorAll('.ce-components-list').forEach(l=>l.classList.remove('ce-active'));
      document.getElementById('list-'+list).classList.add('ce-active');
    });
  });

  /* DEVICE PREVIEW */
  document.querySelectorAll('.ce-device-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const device = btn.dataset.device;
      state.currentDevice = device;
      document.querySelectorAll('.ce-device-btn').forEach(b => b.classList.remove('active'));
      btn.classList.add('active');
      document.getElementById('ceCanvasEdit').className = 'ce-canvas ' + device;
    });
  });

  /* ===== HELPERS ===== */
  const isUI = n => n.closest && (n.closest('.ce-block-handle') || n.classList.contains('ce-add-inside'));
  function getClosestBlock(el){
    while(el && el!==state.editEl){
      if(el.classList && el.classList.contains('ce-block')) return el;
      el=el.parentElement;
    }
    return null;
  }
  function deselect(){
    if(state.selectedBlock){
      state.selectedBlock.classList.remove('ce-selected');
      state.selectedBlock=null;
      renderInspector(null);
    }
  }

  function createBlock(html,key){
    const wrap=document.createElement('div');
    wrap.className='ce-block';
    wrap.dataset.blockId='b'+Date.now()+Math.random().toString(36).slice(2);
    wrap.dataset.key=key;
    wrap.innerHTML=html;

    const handle=document.createElement('div');
    handle.className='ce-block-handle';
    handle.innerHTML=`
      <span class="ce-up" title="Move Up">▲</span>
      <span class="ce-down" title="Move Down">▼</span>
      <span class="ce-dup" title="Duplicate">⧉</span>
      <span class="ce-remove" title="Remove">✕</span>
    `;
    wrap.appendChild(handle);

    if(wrap.querySelector('.ce-slot') && !wrap.querySelector('.ce-add-inside')){
      const addBtn=document.createElement('span');
      addBtn.className='ce-add-inside';
      addBtn.innerHTML=`<i class="fa-solid fa-plus"></i> Add content`;
      const slot=wrap.querySelector('.ce-slot');
      slot.insertAdjacentElement('afterend',addBtn);
    }

    if(key==='ce-column') wrap.style.flex='1';
    attachBlockEvents(wrap);
    bindAddInside(wrap);
    bindHandle(handle, wrap);
    return wrap;
  }

  function bindHandle(handle, block){
    handle.querySelector('.ce-remove').onclick = e=>{
      e.stopPropagation(); pushHistory();
      if(state.selectedBlock===block) deselect();
      block.remove(); syncExport();
    };
    handle.querySelector('.ce-dup').onclick = e=>{
      e.stopPropagation(); pushHistory();
      const clone=block.cloneNode(true);
      clone.dataset.blockId='b'+Date.now()+Math.random().toString(36).slice(2);
      block.insertAdjacentElement('afterend',clone);
      attachBlockEvents(clone);
      bindAddInside(clone);
      clone.querySelectorAll('.ce-block-handle').forEach(h=>bindHandle(h, getClosestBlock(h)));
      syncExport();
    };
    handle.querySelector('.ce-up').onclick = e=>{
      e.stopPropagation(); moveBlock(block,'up');
    };
    handle.querySelector('.ce-down').onclick = e=>{
      e.stopPropagation(); moveBlock(block,'down');
    };
  }

  function moveBlock(block, dir){
    const sib = dir==='up' ? block.previousElementSibling : block.nextElementSibling;
    if(!sib) return;
    pushHistory();
    sib.insertAdjacentElement(dir==='up'?'beforebegin':'afterend', block);
    syncExport();
  }

  function attachBlockEvents(block) {
  // Select / highlight
  block.addEventListener('click', onSelectBlock);

  // Enable dragging of existing blocks (for reorder)
  block.setAttribute('draggable', 'true');
  block.addEventListener('dragstart', onDragStartBlock);

  // NO more dragover() or drop() here!
}


  function bindAddInside(container){
    container.querySelectorAll('.ce-add-inside').forEach(btn=>{
      btn.onclick = function(e){
        e.stopPropagation();
        openAddPopup(btn);
      };
    });
  }

  /* ===== ADD POPUP ===== */
  function openAddPopup(anchor) {
  closePopup();

  const items = Array.from(document.querySelectorAll('#list-elements .ce-component')).map(el => ({
    key: el.dataset.key,
    label: el.textContent.trim()
  }));

  const popup = document.createElement('div');
  popup.className = 'ce-add-popup';
  popup.innerHTML = `<h4>Add element</h4>${items.map(i => `<button data-key="${i.key}">${i.label}</button>`).join('')}`;
  document.body.appendChild(popup);

  const rect = anchor.getBoundingClientRect();
  const margin = 6;

  // Default position: below anchor
  let top = rect.bottom + window.scrollY + margin;
  let left = rect.left + window.scrollX;

  // Temporarily apply the position to measure height
  popup.style.top = top + 'px';
  popup.style.left = left + 'px';

  // Measure the popup after rendering
  const popupHeight = popup.offsetHeight;
  const viewportBottom = window.scrollY + window.innerHeight;

  // If popup would overflow at the bottom, flip to top
  if (top + popupHeight > viewportBottom) {
    top = rect.top + window.scrollY - popupHeight - margin;
    popup.style.top = top + 'px';
  }

  // Handle item clicks
  popup.addEventListener('click', e => {
    if (e.target.tagName === 'BUTTON') {
      const key = e.target.dataset.key;
      const data = getComponentDataByKey(key);
      if (!data) return;

      pushHistory();

      if (key === 'ce-html') {
        const html = prompt('Enter custom HTML:', '<div>Custom</div>');
        if (html !== null) {
          data.html = html;
        } else {
          closePopup();
          return;
        }
      }

      const block = createBlock(data.html, data.key);

      // Slot resolution (column-safe)
      let slot = null;
      const sectionSlot = anchor.closest('.ce-section-slot');
      if (sectionSlot) {
        slot = sectionSlot.querySelector('.ce-slot');
      }

      slot = slot || anchor.previousElementSibling || anchor.parentElement.querySelector('.ce-slot') || anchor.parentElement || state.editEl;

      slot.appendChild(block);
      closePopup();
      syncExport();
    }
  });

  popup.addEventListener('click', e => e.stopPropagation());
  document.addEventListener('click', closePopup, { once: true });
}


  function closePopup(){
    const p=document.querySelector('.ce-add-popup');
    if(p) p.remove();
  }
  function getComponentDataByKey(key){
    const el=document.querySelector(`.ce-component[data-key="${key}"]`);
    if(!el) return null;
    return {key:el.dataset.key, html:el.dataset.html};
  }

  /* ===== INSPECTOR ===== */
  function renderInspector(block){
    const panel=document.getElementById('ceInspector');
    panel.innerHTML='';

    if(!block){
      panel.innerHTML='<p class="ce-muted">Select a block to edit.</p>';
      return;
    }

    const tabs=document.createElement('div');
    tabs.className='ce-prop-tabs';
    tabs.innerHTML=`
      <button class="ce-prop-tab-btn ce-active" data-prop="style">Styling</button>
      <button class="ce-prop-tab-btn" data-prop="content">Content</button>
      ${block.querySelector('.ce-slot')?'<button class="ce-prop-tab-btn" data-prop="actions">Actions</button>':''}
    `;
    const stylePane=document.createElement('div');stylePane.className='ce-prop-pane ce-active';
    const contentPane=document.createElement('div');contentPane.className='ce-prop-pane';
    const actionsPane=document.createElement('div');actionsPane.className='ce-prop-pane';

    panel.appendChild(tabs);
    panel.appendChild(stylePane);
    panel.appendChild(contentPane);
    if(block.querySelector('.ce-slot')) panel.appendChild(actionsPane);

    tabs.addEventListener('click',e=>{
      const btn=e.target.closest('.ce-prop-tab-btn');
      if(!btn) return;
      tabs.querySelectorAll('.ce-prop-tab-btn').forEach(b=>b.classList.remove('ce-active'));
      btn.classList.add('ce-active');
      const which=btn.dataset.prop;
      stylePane.classList.toggle('ce-active', which==='style');
      contentPane.classList.toggle('ce-active', which==='content');
      actionsPane.classList.toggle('ce-active', which==='actions');
    });

    const contentHTML=getInnerHTMLWithoutUI(block);
    const tagNames=['H1','H2','H3','H4','H5','H6','P','A','SPAN','BUTTON','LI'];
    const textNodes=block.querySelectorAll(tagNames.join(','));

    // Add unit toggle
    const unitToggle = document.createElement('div');
    unitToggle.className = 'ce-unit-toggle';
    unitToggle.innerHTML = `
      <button class="${state.currentUnit === 'px' ? 'active' : ''}" data-unit="px">PX</button>
      <button class="${state.currentUnit === '%' ? 'active' : ''}" data-unit="%">%</button>
    `;
    stylePane.appendChild(unitToggle);
    
    unitToggle.querySelectorAll('button').forEach(btn => {
      btn.addEventListener('click', () => {
        state.currentUnit = btn.dataset.unit;
        unitToggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        // Re-render inspector to update units
        renderInspector(block);
      });
    });

    addAlignField(stylePane, block);
    addSpacingField(stylePane, block);
    addRadiusField(stylePane, block);
    addColorBorderField(stylePane, block);
    if(textNodes.length){ addTypographyField(stylePane, textNodes); }
    textNodes.forEach(node=>{
      if(/^H[1-6]$/.test(node.tagName)){ addHeadingLevelField(stylePane, node, block); }
    });
    block.querySelectorAll('a,button').forEach((el,i)=>addButtonStyleField(stylePane, el, i));
    // also let people edit the actual link & target
    block.querySelectorAll('a').forEach((el,i)=>addButtonContentField(contentPane, el, i));

    addImageStyleEditors(stylePane, block);

    addTextEditors(contentPane, textNodes);
    addListEditors(contentPane, block);
    addImageContentEditors(contentPane, block);
    if(block.dataset.key==='ce-html'){ addCustomHTMLField(contentPane, block, contentHTML); }
    if (block.dataset.key === 'ce-social-links') {
  // wrapper for all social link controls
  const socialWrapper = document.createElement('div');
  socialWrapper.className = 'ce-field';
  socialWrapper.innerHTML = `<label>Social Links Layout</label>`;
  contentPane.appendChild(socialWrapper);

  // Ensure layout container is flex for justify-content and alignment
  const flexContainer = block.querySelector('.cs-social-links') || block;
  flexContainer.style.display = 'flex';
  flexContainer.style.alignItems = 'center';

  // Gap control
  const gapField = document.createElement('div');
  gapField.className = 'ce-field';
  const firstLink = block.querySelector('a');
  let currentGap = 12;
  if (firstLink) {
    const computed = getComputedStyle(firstLink);
    currentGap = parseInt(computed.marginRight) || currentGap;
  }
  gapField.innerHTML = `
    <label>Icon Gap (px)</label>
    <div style="display:flex;gap:8px;align-items:center;">
      <input type="number" min="0" value="${currentGap}" style="width:70px;" id="socialGapInput" />
      <div class="small text-muted">Space between icons</div>
    </div>`;
  gapField.querySelector('#socialGapInput').addEventListener('input', e => {
    pushHistory();
    const gap = e.target.value + 'px';
    block.querySelectorAll('a').forEach((a, idx, arr) => {
      a.style.marginRight = idx === arr.length - 1 ? '0' : gap;
    });
    syncExport();
  });
  contentPane.appendChild(gapField);

  // Then existing per-icon URL / color / remove logic:
  const links = ['facebook', 'twitter', 'instagram', 'linkedin'];
  links.forEach(name => {
    const a = block.querySelector(`a[href*="${name}.com"]`);
    if (!a) return;
    const i = a.querySelector('i');
    const fld = document.createElement('div');
    fld.className = 'ce-field';

    const labelUrl = name.charAt(0).toUpperCase() + name.slice(1) + ' URL';
    const urlInput = document.createElement('input');
    urlInput.type = 'text';
    urlInput.value = a.href;
    urlInput.placeholder = `https://${name}.com/…`;
    urlInput.addEventListener('input', e => {
      pushHistory();
      a.href = e.target.value;
      syncExport();
    });

    const labelColor = 'Icon Color';
    const colorInput = document.createElement('input');
    colorInput.type = 'color';
    const currentColor = window.getComputedStyle(i || a).color;
    colorInput.value = rgb2hex(currentColor);
    colorInput.addEventListener('input', e => {
      pushHistory();
      if (i) i.style.color = e.target.value;
      else a.style.color = e.target.value;
      syncExport();
    });

    const removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'ce-btn-sm';
    removeBtn.innerHTML = 'Remove';
    removeBtn.addEventListener('click', () => {
      pushHistory();
      a.remove();
      syncExport();
      renderInspector(block); // re-render to reflect removal
    });

    const title = document.createElement('div');
    title.innerHTML = `<strong>${name.charAt(0).toUpperCase() + name.slice(1)}</strong>`;
    fld.appendChild(title);
    fld.innerHTML += `<label>${labelUrl}</label>`;
    fld.appendChild(urlInput);
    fld.appendChild(document.createElement('br'));
    fld.innerHTML += `<label>${labelColor}</label>`;
    fld.appendChild(colorInput);
    fld.appendChild(document.createElement('br'));
    fld.appendChild(removeBtn);

    contentPane.appendChild(fld);
  });
}




    if(block.querySelector('.ce-slot')){
      const f=document.createElement('div');f.className='ce-field';
      f.innerHTML=`<button class="ce-btn-sm ce-primary" id="propAddBtn"><i class="fa-solid fa-plus"></i> Add element here</button>`;
      f.querySelector('#propAddBtn').addEventListener('click',()=>openAddPopup(block.querySelector('.ce-add-inside')||block));
      actionsPane.appendChild(f);
    }
  }

  /* === styling/content helpers === */
  function addAlignField(panel, block){
    const isSocial = block.dataset.key === 'ce-social-links';
    let currentAlign = 'left';

    if (isSocial) {
      const flexContainer = block.querySelector('.cs-social-links') || block;
      // ensure it's flex so justify-content will apply
      flexContainer.style.display = 'flex';
      flexContainer.style.alignItems = 'center';
      const justify = getComputedStyle(flexContainer).justifyContent;
      if (justify === 'center') currentAlign = 'center';
      else if (justify === 'flex-end') currentAlign = 'right';
      else currentAlign = 'left';
    } else {
      currentAlign = getComputedStyle(block).textAlign || 'left';
    }

    const field = document.createElement('div');
    field.className = 'ce-field';
    field.innerHTML = `
      <label>Horizontal Align</label>
      <div class="ce-align-group">
        <button class="ce-align-btn ${currentAlign === 'left' ? 'active' : ''}" data-align="left"><i class="fa-solid fa-align-left"></i></button>
        <button class="ce-align-btn ${currentAlign === 'center' ? 'active' : ''}" data-align="center"><i class="fa-solid fa-align-center"></i></button>
        <button class="ce-align-btn ${currentAlign === 'right' ? 'active' : ''}" data-align="right"><i class="fa-solid fa-align-right"></i></button>
      </div>`;

    field.querySelectorAll('.ce-align-btn').forEach(b => {
      b.addEventListener('click', () => {
        pushHistory();
        const align = b.dataset.align;
        field.querySelectorAll('.ce-align-btn').forEach(x => x.classList.remove('active'));
        b.classList.add('active');

        if (isSocial) {
          const flexContainer = block.querySelector('.cs-social-links') || block;
          if (align === 'center') flexContainer.style.justifyContent = 'center';
          else if (align === 'right') flexContainer.style.justifyContent = 'flex-end';
          else flexContainer.style.justifyContent = 'flex-start';
        } else {
          block.style.textAlign = align;
        }
        syncExport();
      });
    });

    panel.appendChild(field);
  }


  function addSpacingField(panel, block){
    const cs=getComputedStyle(block);
    const mt=parseInt(cs.marginTop)||0, mr=parseInt(cs.marginRight)||0, mb=parseInt(cs.marginBottom)||0, ml=parseInt(cs.marginLeft)||0;
    const pt=parseInt(cs.paddingTop)||0, pr=parseInt(cs.paddingRight)||0, pb=parseInt(cs.paddingBottom)||0, pl=parseInt(cs.paddingLeft)||0;
    
    const field=document.createElement('div');field.className='ce-field';
    field.innerHTML=`<label>Margin (${state.currentUnit})</label>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;margin-bottom:6px;">
        <input type="number" value="${mt}" data-prop="marginTop" placeholder="Top">
        <input type="number" value="${mr}" data-prop="marginRight" placeholder="Right">
        <input type="number" value="${mb}" data-prop="marginBottom" placeholder="Bottom">
        <input type="number" value="${ml}" data-prop="marginLeft" placeholder="Left">
      </div>
      <label>Padding (${state.currentUnit})</label>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">
        <input type="number" value="${pt}" data-prop="paddingTop" placeholder="Top">
        <input type="number" value="${pr}" data-prop="paddingRight" placeholder="Right">
        <input type="number" value="${pb}" data-prop="paddingBottom" placeholder="Bottom">
        <input type="number" value="${pl}" data-prop="paddingLeft" placeholder="Left">
      </div>`;
    
    field.querySelectorAll('input').forEach(inp=>{
      inp.addEventListener('input',e=>{
        pushHistory(); 
        const value = e.target.value ? e.target.value + state.currentUnit : '';
        block.style[e.target.dataset.prop] = value;
        syncExport();
      });
    });
    panel.appendChild(field);
  }

  function addRadiusField(panel, block){
    const cs=getComputedStyle(block);
    const tl=parseInt(cs.borderTopLeftRadius)||0,tr=parseInt(cs.borderTopRightRadius)||0,br=parseInt(cs.borderBottomRightRadius)||0,bl=parseInt(cs.borderBottomLeftRadius)||0;
    block.style.overflow = (tl||tr||br||bl) ? 'hidden' : '';
    const field=document.createElement('div');field.className='ce-field';
    field.innerHTML=`<label>Border Radius (${state.currentUnit})</label>
      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:6px;">
        <input type="number" value="${tl}" data-prop="borderTopLeftRadius" placeholder="Top Left">
        <input type="number" value="${tr}" data-prop="borderTopRightRadius" placeholder="Top Right">
        <input type="number" value="${br}" data-prop="borderBottomRightRadius" placeholder="Bottom Right">
        <input type="number" value="${bl}" data-prop="borderBottomLeftRadius" placeholder="Bottom Left">
      </div>`;
    field.querySelectorAll('input').forEach(inp=>{
      inp.addEventListener('input',e=>{
        pushHistory(); 
        const value = e.target.value ? e.target.value + state.currentUnit : '';
        block.style[e.target.dataset.prop] = value; 
        syncExport();
      });
    });
    panel.appendChild(field);
  }

  function addColorBorderField(panel, block){
    const cs=getComputedStyle(block);
    const txtColor = rgb2hex(cs.color);
    const bgColor  = rgb2hex(cs.backgroundColor);
    const borderColor = rgb2hex(cs.borderColor);
    const borderWidth = parseInt(cs.borderWidth)||0;
    const borderStyle = cs.borderStyle || 'none';
    const field=document.createElement('div');field.className='ce-field';
    field.innerHTML=`<label>Text / Background</label>
      <div class="ce-color-pair">
        <input type="color" value="${txtColor}" title="Text color">
        <input type="color" value="${bgColor}"  title="Background color">
      </div>
      <label style="margin-top:10px;">Border</label>
      <div style="display:flex;gap:6px;margin-bottom:6px;">
        <input type="number" min="0" value="${borderWidth}" style="width:60px;" title="Width (px)">
        <select style="flex:1;">
          <option value="none"   ${borderStyle==='none'?'selected':''}>none</option>
          <option value="solid"  ${borderStyle==='solid'?'selected':''}>solid</option>
          <option value="dashed" ${borderStyle==='dashed'?'selected':''}>dashed</option>
          <option value="dotted" ${borderStyle==='dotted'?'selected':''}>dotted</option>
        </select>
        <input type="color" value="${borderColor}" title="Border color" style="width:34px;height:34px;padding:0;border:none;">
      </div>`;
    const [txtInp,bgInp]=field.querySelectorAll('.ce-color-pair input');
    const inputs = field.querySelectorAll('input,select');
    const bWidth=inputs[2], bStyle=inputs[3], bColor=inputs[4];
    txtInp.addEventListener('input',e=>{ pushHistory(); block.style.color=e.target.value; syncExport();});
    bgInp.addEventListener('input',e=>{ pushHistory(); block.style.backgroundColor=e.target.value; syncExport();});
    bWidth.addEventListener('input',e=>{ pushHistory(); block.style.borderWidth=e.target.value+'px'; syncExport();});
    bStyle.addEventListener('change',e=>{ pushHistory(); block.style.borderStyle=e.target.value; syncExport();});
    bColor.addEventListener('input',e=>{ pushHistory(); block.style.borderColor=e.target.value; syncExport();});
    panel.appendChild(field);
  }

  function addTypographyField(panel, nodes){
    const field=document.createElement('div');field.className='ce-field';
    field.innerHTML=`<label>Typography</label>
      <div style="display:flex;gap:6px;margin-bottom:6px;">
        <select class="ce-font-family" style="flex:1;">
          <option value="">Default</option>
          <option value="Inter, sans-serif">Inter</option>
          <option value="Poppins, sans-serif">Poppins</option>
          <option value="Arial, sans-serif">Arial</option>
          <option value="Georgia, serif">Georgia</option>
          <option value="'Times New Roman', serif">Times New Roman</option>
        </select>
        <input type="number" class="ce-font-size" min="8" max="96" placeholder="px" style="width:70px;" />
      </div>
      <div class="ce-typo-tools">
        <button class="ce-typo-btn" data-style="bold"><i class="fa-solid fa-bold"></i></button>
        <button class="ce-typo-btn" data-style="italic"><i class="fa-solid fa-italic"></i></button>
        <button class="ce-typo-btn" data-style="underline"><i class="fa-solid fa-underline"></i></button>
      </div>`;
    const ff=field.querySelector('.ce-font-family');
    const fs=field.querySelector('.ce-font-size');
    ff.addEventListener('change',e=>{ pushHistory(); nodes.forEach(n=>n.style.fontFamily=e.target.value||''); syncExport();});
    fs.addEventListener('input',e=>{ pushHistory(); nodes.forEach(n=> n.style.fontSize=e.target.value?e.target.value+'px':''); syncExport();});
    field.querySelectorAll('.ce-typo-btn').forEach(btn=>{
      btn.addEventListener('click',()=>{
        pushHistory();
        const st=btn.dataset.style;
        btn.classList.toggle('active');
        nodes.forEach(n=>{
          if(st==='bold') n.style.fontWeight=btn.classList.contains('active')?'700':'';
          if(st==='italic') n.style.fontStyle=btn.classList.contains('active')?'italic':'';
          if(st==='underline') n.style.textDecoration=btn.classList.contains('active')?'underline':'';
        });
        syncExport();
      });
    });
    panel.appendChild(field);
  }

  function addHeadingLevelField(panel,node,block){
    const field=document.createElement('div');field.className='ce-field';
    field.innerHTML=`<label>Heading Level</label>
      <select class="ce-heading-level">
        <option value="H1">H1</option><option value="H2">H2</option><option value="H3">H3</option>
        <option value="H4">H4</option><option value="H5">H5</option><option value="H6">H6</option>
      </select>`;
    const sel=field.querySelector('.ce-heading-level');
    sel.value=node.tagName;
    sel.addEventListener('change',e=>{
      pushHistory();
      const newTag=e.target.value;
      const newNode=document.createElement(newTag);
      Array.from(node.attributes).forEach(a=>newNode.setAttribute(a.name,a.value));
      newNode.innerHTML=node.innerHTML;
      node.parentNode.replaceChild(newNode,node);
      renderInspector(block);
      syncExport();
    });
    panel.appendChild(field);
  }

  function addButtonStyleField(panel, el, idx){
    const cs=getComputedStyle(el);
    const bg=rgb2hex(cs.backgroundColor), color=rgb2hex(cs.color), bW=parseInt(cs.borderWidth)||0, bCol=rgb2hex(cs.borderColor), bSty=cs.borderStyle||'none', bRad=parseInt(cs.borderTopLeftRadius)||0;
    const field=document.createElement('div');field.className='ce-field';
    field.innerHTML=`<label>Button ${idx+1} Colors</label>
      <div class="ce-color-pair" style="margin-bottom:6px;">
        <input type="color" value="${bg}" title="Background">
        <input type="color" value="${color}" title="Text">
      </div>
      <label>Border</label>
      <div style="display:flex;gap:6px;margin-bottom:6px;">
        <input type="number" min="0" value="${bW}" style="width:60px;" data-btn-prop="borderWidth">
        <select style="flex:1;" data-btn-prop="borderStyle">
          <option value="none" ${bSty==='none'?'selected':''}>none</option>
          <option value="solid" ${bSty==='solid'?'selected':''}>solid</option>
          <option value="dashed" ${bSty==='dashed'?'selected':''}>dashed</option>
          <option value="dotted" ${bSty==='dotted'?'selected':''}>dotted</option>
          <option value="double" ${bSty==='double'?'selected':''}>double</option>
        </select>
        <input type="color" value="${bCol}" style="width:34px;height:34px;padding:0;border:none;" data-btn-prop="borderColor">
      </div>
      <label>Border Radius (${state.currentUnit})</label>
      <input type="number" min="0" value="${bRad}" data-btn-prop="borderRadius">`;
    const [bgInp,txtInp]=field.querySelectorAll('.ce-color-pair input');
    bgInp.addEventListener('input',e=>{ pushHistory(); el.style.backgroundColor=e.target.value; el.style.borderColor=e.target.value; syncExport();});
    txtInp.addEventListener('input',e=>{ pushHistory(); el.style.color=e.target.value; syncExport();});
    field.querySelectorAll('[data-btn-prop]').forEach(inp=>{
      inp.addEventListener('input',e=>{
        pushHistory();
        const p=e.target.dataset.btnProp;
        let v=e.target.value;
        if(p==='borderWidth'||p==='borderRadius') {
          v = v ? v + (p === 'borderRadius' ? state.currentUnit : 'px') : '';
        }
        el.style[p]=v; syncExport();
      });
      if(inp.tagName==='SELECT'){
        inp.addEventListener('change',e=>{ pushHistory(); el.style[e.target.dataset.btnProp]=e.target.value; syncExport();});
      }
    });
    panel.appendChild(field);
  }

  function addImageStyleEditors(panel, block){
    const imgs=block.querySelectorAll('img');
    imgs.forEach((img, idx)=>{
      if(isUI(img)) return;
      const parent = img.parentElement;
      const parentAlign = getComputedStyle(parent).textAlign;
      let current = parentAlign==='center' ? 'center' : (parentAlign==='right' ? 'right' : 'left');
      const alignWrap=document.createElement('div');alignWrap.className='ce-field';
      alignWrap.innerHTML=`<label>Image ${idx+1} Align</label>
        <div class="ce-img-align">
          <button class="ce-img-align-btn ${current==='left'?'active':''}" data-img-align="left"><i class="fa-solid fa-align-left"></i></button>
          <button class="ce-img-align-btn ${current==='center'?'active':''}" data-img-align="center"><i class="fa-solid fa-align-center"></i></button>
          <button class="ce-img-align-btn ${current==='right'?'active':''}" data-img-align="right"><i class="fa-solid fa-align-right"></i></button>
        </div>`;
      alignWrap.querySelectorAll('.ce-img-align-btn').forEach(btn=>{
        btn.addEventListener('click',()=>{
          pushHistory();
          const val=btn.dataset.imgAlign;
          img.style.display='inline-block';
          parent.style.textAlign=val;
          alignWrap.querySelectorAll('.ce-img-align-btn').forEach(x=>x.classList.remove('active'));
          btn.classList.add('active');
          syncExport();
        });
      });
      panel.appendChild(alignWrap);
    });
  }

  // replace your existing addTextEditors with this:
  function addTextEditors(panel, nodes) {
  nodes.forEach((node, idx) => {
    if (isUI(node)) return;

    const wrap = document.createElement('div');
    wrap.className = 'ce-field';
    wrap.innerHTML = `
      <label>Text ${idx + 1}</label>
      <div class="ce-text-toolbar">
        <button type="button" data-cmd="bold"><b>B</b></button>
        <button type="button" data-cmd="italic"><i>I</i></button>
        <button type="button" data-cmd="underline"><u>U</u></button>
        <button type="button" data-cmd="strikethrough"><s>S</s></button>
        <button type="button" data-cmd="createLink">🔗</button>
        <input type="color" data-cmd="foreColor" title="Text color">

        <div class="ce-font-controls">
          <select data-cmd="fontName">
            <option value="Arial">Arial</option>
            <option value="Georgia">Georgia</option>
            <option value="Tahoma">Tahoma</option>
            <option value="Times New Roman">Times New Roman</option>
            <option value="Verdana">Verdana</option>
          </select>
          <select data-cmd="fontSize">
            <option value="1">10px</option>
            <option value="2">13px</option>
            <option value="3" selected>16px</option>
            <option value="4">18px</option>
            <option value="5">24px</option>
            <option value="6">32px</option>
            <option value="7">48px</option>
          </select>
        </div>
      </div>
      <div class="ce-text-area" contenteditable="true">${node.innerHTML}</div>
    `;

    // toolbar buttons
    wrap.querySelectorAll('.ce-text-toolbar button').forEach(btn => {
      btn.addEventListener('click', () => {
        const cmd = btn.dataset.cmd;
        let val = null;
        if (cmd === 'createLink') {
          val = prompt('Enter URL:', 'https://');
          if (!val) return;
        }
        document.execCommand(cmd, false, val);
        wrap.querySelector('.ce-text-area').focus();
      });
    });

    // selects & color picker
    wrap.querySelectorAll('.ce-text-toolbar select, .ce-text-toolbar input[type="color"]')
        .forEach(ctrl => {
      ctrl.addEventListener('change', e => {
        document.execCommand(ctrl.dataset.cmd, false, e.target.value);
        wrap.querySelector('.ce-text-area').focus();
      });
    });

    // sync back into the real node
    const editable = wrap.querySelector('.ce-text-area');
    editable.addEventListener('input', () => {
      pushHistory();
      node.innerHTML = editable.innerHTML;
      syncExport();
    });

    panel.appendChild(wrap);
  });
}




  function addListEditors(panel, block){
    const lists = block.querySelectorAll('ul,ol');
    lists.forEach((list, idx)=>{
      if(isUI(list)) return;
      const field=document.createElement('div');field.className='ce-field';
      field.innerHTML=`<label>List ${idx+1} Items</label>
        <div class="ce-list-items" style="margin-bottom:6px;"></div>
        <button type="button" class="ce-btn-sm ce-primary ce-add-li"><i class="fa-solid fa-plus"></i> Add item</button>`;
      const itemsWrap = field.querySelector('.ce-list-items');

      function makeRow(li){
        const row=document.createElement('div');row.style.display='flex';row.style.gap='6px';row.style.marginBottom='4px';
        row.innerHTML=`<input type="text" value="${li.textContent}" style="flex:1;">
          <button type="button" class="ce-btn-sm ce-del-li"><i class="fa-solid fa-trash"></i></button>`;
        row.querySelector('input').addEventListener('input',e=>{
          pushHistory(); li.textContent=e.target.value; syncExport();
        });
        row.querySelector('.ce-del-li').addEventListener('click',()=>{
          pushHistory(); li.remove(); row.remove(); syncExport();
        });
        return row;
      }

      Array.from(list.children).forEach(li=>{
        if(li.tagName!=='LI') return;
        itemsWrap.appendChild(makeRow(li));
      });

      field.querySelector('.ce-add-li').addEventListener('click',()=>{
        pushHistory();
        const li=document.createElement('li');li.textContent='New item';
        list.appendChild(li);
        itemsWrap.appendChild(makeRow(li));
        syncExport();
      });

      panel.appendChild(field);
    });
  }

  function addImageContentEditors(panel, block){
    const imgs=block.querySelectorAll('img');
    imgs.forEach((img, idx)=>{
      if(isUI(img)) return;
      const wrap=document.createElement('div');wrap.className='ce-field';
      wrap.innerHTML=`<label>Image ${idx+1} URL</label><input type="text" value="${img.src}">`;
      wrap.querySelector('input').addEventListener('input',e=>{
        pushHistory(); img.src=e.target.value; syncExport();
      });
      panel.appendChild(wrap);

      const sizeWrap=document.createElement('div'); sizeWrap.className='ce-field';
      const w=img.getAttribute('width')||''; const h=img.getAttribute('height')||'';
      sizeWrap.innerHTML=`<label>Width / Height</label>
        <div style="display:flex;gap:6px;">
          <input type="number" min="0" placeholder="W" value="${w}" style="flex:1;">
          <input type="number" min="0" placeholder="H" value="${h}" style="flex:1;">
        </div>
        <div class="ce-unit-toggle" style="margin-top:6px;">
          <button class="${w.toString().includes('%') ? 'active' : ''}" data-unit="%">%</button>
          <button class="${w.toString().includes('px') || !w ? 'active' : ''}" data-unit="px">PX</button>
        </div>`;
      const [wInp,hInp]=sizeWrap.querySelectorAll('input');
      const unitToggle = sizeWrap.querySelector('.ce-unit-toggle');
      
      // Set initial unit
      let currentUnit = w.toString().includes('%') ? '%' : 'px';
      
      wInp.addEventListener('input',e=>{
        pushHistory(); 
        const value = e.target.value ? e.target.value + currentUnit : '';
        img.setAttribute('width', value); 
        syncExport();
      });
      hInp.addEventListener('input',e=>{
        pushHistory(); 
        const value = e.target.value ? e.target.value + currentUnit : '';
        img.setAttribute('height', value); 
        syncExport();
      });
      
      unitToggle.querySelectorAll('button').forEach(btn => {
        btn.addEventListener('click', () => {
          currentUnit = btn.dataset.unit;
          unitToggle.querySelectorAll('button').forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          
          // Update values with new unit
          const wVal = wInp.value ? wInp.value + currentUnit : '';
          const hVal = hInp.value ? hInp.value + currentUnit : '';
          if (wVal) img.setAttribute('width', wVal);
          if (hVal) img.setAttribute('height', hVal);
          syncExport();
        });
      });
      
      panel.appendChild(sizeWrap);
    });
  }

  function addCustomHTMLField(panel, block, html){
    const htmlField=document.createElement('div');htmlField.className='ce-field';
    htmlField.innerHTML=`<label>Custom HTML</label><textarea>${html}</textarea>`;
    htmlField.querySelector('textarea').addEventListener('change',e=>{
      pushHistory();
      const handle=block.querySelector('.ce-block-handle');
      const addBtns=[...block.querySelectorAll('.ce-add-inside')];
      block.innerHTML=e.target.value;
      if(handle) block.appendChild(handle);
      addBtns.forEach(a=>block.appendChild(a));
      bindAddInside(block);
      syncExport();
    });
    panel.appendChild(htmlField);
  }
  /**
 * In the Content pane, let the user edit the href & target of each <a>
 */
function addButtonContentField(panel, el, idx) {
  const field = document.createElement('div');
  field.className = 'ce-field';
  field.innerHTML = `
    <label>Button ${idx+1} Link URL</label>
    <input type="text" class="ce-btn-link-url" placeholder="https://example.com" value="${el.getAttribute('href')||''}" />
    <div style="margin-top:8px;">
      <label><input type="checkbox" class="ce-btn-link-target" ${el.target === '_blank' ? 'checked' : ''}/> Open in new tab</label>
    </div>
  `;

  const urlInput = field.querySelector('.ce-btn-link-url');
  const targetCheckbox = field.querySelector('.ce-btn-link-target');

  urlInput.addEventListener('input', e => {
    pushHistory();
    el.setAttribute('href', e.target.value);
    syncExport();
  });

  targetCheckbox.addEventListener('change', e => {
    pushHistory();
    if (e.target.checked) {
      el.setAttribute('target', '_blank');
      el.setAttribute('rel', 'noopener noreferrer');
    } else {
      el.removeAttribute('target');
      el.removeAttribute('rel');
    }
    syncExport();
  });

  panel.appendChild(field);
}


  /* ===== CLEAN & SYNC ===== */
  function getInnerHTMLWithoutUI(el){
    const clone=el.cloneNode(true);
    clone.querySelectorAll('.ce-block-handle,.ce-add-inside,.ce-drop-marker').forEach(x=>x.remove());
    return clone.innerHTML.trim();
  }

  function cleanCloneFromEdit(){
    const clone=state.editEl.cloneNode(true);
    clone.querySelectorAll('.ce-block-handle,.ce-add-inside,.ce-drop-marker').forEach(x=>x.remove());
    clone.querySelectorAll('.ce-block').forEach(b=>{
      b.classList.remove('ce-block','ce-selected','ce-prop-active');
      b.removeAttribute('data-block-id');
      b.removeAttribute('data-key');
    });
    clone.querySelectorAll('.ce-slot').forEach(s=>s.replaceWith(...s.childNodes));
    return clone;
  }

  function syncExport(){
  // 1) clean & mirror for export
  const cleaned = cleanCloneFromEdit();
  state.outEl.innerHTML = cleaned.innerHTML;

  // 2) **raw** draft: the full editor inner HTML, handles & all
  document.getElementById('ceDraftExport').value = state.editEl.innerHTML;
}

  function rebuildEditFromExport(html){
    state.editEl.innerHTML='';
    const tmp=document.createElement('div'); tmp.innerHTML=html;
    Array.from(tmp.childNodes).forEach(n=>{
      if(n.nodeType===1){
        const wrap=createBlock(n.outerHTML,'custom');
        state.editEl.appendChild(wrap);
      }else if(n.nodeType===3 && n.textContent.trim()){
        const wrap=createBlock(`<p>${n.textContent}</p>`,'ce-paragraph');
        state.editEl.appendChild(wrap);
      }
    });
    rebindAll();
    syncExport();
  }

  function rgb2hex(rgb){
    if(!rgb || rgb==='transparent') return '#ffffff';
    const m=rgb.match(/\d+/g); if(!m) return '#ffffff';
    const r=parseInt(m[0]).toString(16).padStart(2,'0');
    const g=parseInt(m[1]).toString(16).padStart(2,'0');
    const b=parseInt(m[2]).toString(16).padStart(2,'0');
    return '#'+r+g+b;
  }

  /* ===== DRAG from palette ===== */
  function onDragStartComponent(e){
    const el=e.currentTarget;
    e.dataTransfer.setData('text/plain', JSON.stringify({ key: el.dataset.key, html: el.dataset.html }));
    e.dataTransfer.effectAllowed='copy';
  }

  /* ===== CANVAS DnD ===== */
  function onDragOverCanvas(e){
    e.preventDefault();
    const marker=state.markerEl;
    const target=getClosestBlock(e.target);
    if(target){
      const rect=target.getBoundingClientRect();
      const before=(e.clientY-rect.top) < rect.height/2;
      marker.classList.add('active');
      target.insertAdjacentElement(before?'beforebegin':'afterend',marker);
      marker.dataset.position=before?'before':'after';
      marker.dataset.targetId=target.dataset.blockId;
    }else{
      state.editEl.appendChild(marker);
      marker.classList.add('active');
      marker.dataset.position='end';
      marker.dataset.targetId='';
    }
  }
  function onDropCanvas(e){
    e.preventDefault();
    const raw=e.dataTransfer.getData('text/plain');
    if(!raw) return;
    let data; try{data=JSON.parse(raw);}catch(_){return;}
    pushHistory();
    if(data.key==='ce-html'){
      const html=prompt('Enter custom HTML:','<div>Custom</div>');
      if(html!==null) data.html=html;
    }
    const block=createBlock(data.html,data.key);
    const marker=state.markerEl;
    const id=marker.dataset.targetId;
    const pos=marker.dataset.position;
    marker.classList.remove('active');
    if(id){
      const target=state.editEl.querySelector(`[data-block-id="${id}"]`);
      target.insertAdjacentElement(pos==='before'?'beforebegin':'afterend',block);
    }else{
      state.editEl.appendChild(block);
    }

    block.querySelectorAll('.ce-slot').forEach(slot=>{
      const parent = slot.parentElement;
      if(parent && !parent.classList.contains('ce-block')){
        const colBlock = createBlock(parent.innerHTML,'ce-column');
        colBlock.style.flex='1';
        parent.replaceWith(colBlock);
      }
    });

    syncExport();
  }
  function onDragStartBlock(e){
    const b=getClosestBlock(e.target);
    if(!b) return;
    e.dataTransfer.setData('application/x-ce-block', b.dataset.blockId);
    e.dataTransfer.effectAllowed='move';
  }

  /* ===== SELECT ===== */
  function onSelectBlock(e){
    e.stopPropagation();
    const block=getClosestBlock(e.target);
    if(!block) return;
    deselect();
    state.selectedBlock=block;
    block.classList.add('ce-selected');
    renderInspector(block);
  }

  /* ===== EXPORT ===== */
  // Replace your existing getExportHTML() with this:
  function getExportHTML(){
  // 1) grab the cleaned inner HTML
  const inner = state.outEl.innerHTML.trim();

  // 2) return the full email template
  return `<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Your Campaign</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
  <style>
    /* Reset */
    body, table, td { margin:0; padding:0; }
    img { border:none; display:block; max-width:100%; height:auto; }
    a { text-decoration:none; }

    /* Wrapper tables */
    .wrapper { width:100% !important; background:#f3f4f6; }
    .inner   {
      width:100% !important;
      max-width:768px;
      margin:0 auto;
      background:#ffffff;
      box-shadow:0 1px 3px rgba(0,0,0,0.1);
    }

    /* Mobile tweaks */
    @media only screen and (max-width:600px) {
      .inner { box-shadow:none !important; }
    }
      .ce-section-slot-wrapper {
        width:100% !important;
        max-width:768px;
        display: flex;
        }

        /* this must come *after* your base rule */
        @media (max-width: 375px) {
        .ce-section-slot-wrapper {
            flex-direction: column !important;
        }
        }
  </style>
</head>
<body>
  <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" border="0" align="center">
    <tr>
      <td align="center">
        <table class="inner" cellpadding="0" cellspacing="0" border="0" align="center">
          <tr>
            <td style="padding:24px; font-family:Arial,sans-serif; color:#111827;">
              ${inner}
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>`;
}


  function exportHTML(){
    const html=getExportHTML();
    document.getElementById('ceExport').value=html;
    document.getElementById('ceModal').style.display='flex';
  }

  /* ===== CODE TAB ===== */
  function loadCodeFromExport(){
    const html=getExportHTML();
    document.getElementById('ceCodeArea').value=html;
    refreshPreview(html);
  }
  function refreshPreview(html){
    const iframe=document.getElementById('ceCodePreview');
    iframe.contentDocument.open();iframe.contentDocument.write(html);iframe.contentDocument.close();
  }
  function applyCodeToCanvas(){
    const txt=document.getElementById('ceCodeArea').value;
    state.outEl.innerHTML=txt;
    rebuildEditFromExport(txt);
    refreshPreview(txt);
  }

  function rebindAll(){
    deselect();
    state.editEl.querySelectorAll('.ce-block').forEach(b=>attachBlockEvents(b));
    state.editEl.querySelectorAll('.ce-block-handle').forEach(h=>{
      const blk=getClosestBlock(h);
      bindHandle(h, blk);
    });
    bindAddInside(state.editEl);
  }

  /* ===== BOOT ===== */
  window.addEventListener('DOMContentLoaded', () => {
    document
    .getElementById('ceCanvasEdit')
    .addEventListener('click', e => {
      // if you clicked on an <a> (or inside one), stop it
      if (e.target.closest('a')) {
        e.preventDefault();
      }
    });
    
  state.editEl   = document.getElementById('ceCanvasEdit');
  state.outEl    = document.getElementById('ceCanvasExport');
  state.markerEl = document.createElement('div');
  state.markerEl.className = 'ce-drop-marker';

  // 1) Only the canvas handles dragover & drop
  state.editEl.addEventListener('dragover', onDragOverCanvas);
  state.editEl.addEventListener('drop',    onDropCanvas);

  // 2) Close inspector/popup on canvas click
  state.editEl.addEventListener('click', () => { deselect(); closePopup(); });

  // 3) Only palette items (elements & sections) start a new drag
  document
    .querySelectorAll('#list-elements .ce-component, #list-sections .ce-component')
    .forEach(el => el.addEventListener('dragstart', onDragStartComponent));

  // 4) Modal close button
  document.getElementById('ceCloseModal')
    .addEventListener('click', () => {
      document.getElementById('ceModal').style.display = 'none';
    });

  // 5) Code tab buttons
  document.getElementById('ceCodeRefresh')
    .addEventListener('click', loadCodeFromExport);
  document.getElementById('ceCodeApply')
    .addEventListener('click', applyCodeToCanvas);

  // 6) Undo / Redo
  document.getElementById('ceUndo')
    .addEventListener('click', undo);
  document.getElementById('ceRedo')
    .addEventListener('click', redo);

  // 7) Keyboard shortcuts
  document.addEventListener('keydown', e => {
    if ((e.ctrlKey || e.metaKey) && !e.shiftKey && e.key.toLowerCase() === 'z') {
      e.preventDefault();
      undo();
    }
    if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key.toLowerCase() === 'z') {
      e.preventDefault();
      redo();
    }
    if (state.selectedBlock && e.altKey && e.key === 'ArrowUp') {
      e.preventDefault();
      moveBlock(state.selectedBlock, 'up');
    }
    if (state.selectedBlock && e.altKey && e.key === 'ArrowDown') {
      e.preventDefault();
      moveBlock(state.selectedBlock, 'down');
    }
  });

  // 8) Initialize history & export
  pushHistory();
  syncExport();
});

  window.CEBuilder = {
    getHTML: getExportHTML,
    setHTML(html) {
      rebuildEditFromExport(html || '');
      syncExport();
    },
    getDraft() {
      return document.getElementById('ceDraftExport').value;
    }
  };


})();
</script>
</body>
</html>