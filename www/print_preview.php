<?php
header('Content-Type: text/html; charset=UTF-8');
require_once 'config.php';
ensurePrintTemplates();

$notice_id = isset($_GET['notice_id']) ? intval($_GET['notice_id']) : 0;
$template_id = isset($_GET['template_id']) ? intval($_GET['template_id']) : 0;

if ($notice_id <= 0) {
    header("Location: search_notice.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>打印预览 - 公告信息管理系统</title>
    <link rel="stylesheet" href="style.css">
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+SC:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            background: #f0f2f5;
            font-family: 'Noto Sans SC', sans-serif;
        }
        .preview-toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 12px 24px;
            display: flex;
            align-items: center;
            gap: 16px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
        }
        .toolbar-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .toolbar-title {
            font-size: 16px;
            font-weight: 600;
            color: #111827;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            color: #374151;
            text-decoration: none;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .back-btn:hover {
            border-color: #9ca3af;
            background: #f9fafb;
        }
        .template-select {
            padding: 8px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            background: white;
            color: #374151;
            font-size: 14px;
            cursor: pointer;
            min-width: 180px;
        }
        .template-select:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        .action-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 20px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .action-btn svg { width: 16px; height: 16px; }
        .btn-print {
            background: #3b82f6;
            color: white;
        }
        .btn-print:hover { background: #2563eb; }
        .btn-pdf {
            background: #10b981;
            color: white;
        }
        .btn-pdf:hover { background: #059669; }
        .btn-pdf:disabled, .btn-print:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .preview-container {
            max-width: 900px;
            margin: 24px auto;
            padding: 0 24px 48px;
        }
        .loading {
            text-align: center;
            padding: 80px 24px;
            color: #6b7280;
        }
        .loading svg {
            width: 48px;
            height: 48px;
            animation: spin 1s linear infinite;
            margin-bottom: 16px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        #print-area {
            background: white;
            min-height: 1100px;
            position: relative;
        }

        /* ===== Minimal Template ===== */
        .tpl-minimal {
            padding: 60px 50px;
            font-family: var(--tpl-font, 'Noto Sans SC', sans-serif);
            color: var(--tpl-text, #333);
            background: var(--tpl-bg, #fff);
            font-size: var(--tpl-fontsize, 14px);
            line-height: 1.8;
        }
        .tpl-minimal .tpl-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-bottom: 20px;
            border-bottom: 2px solid var(--tpl-border, #e5e7eb);
            margin-bottom: 32px;
            background: var(--tpl-header-bg, transparent);
            padding: 16px 0 20px;
        }
        .tpl-minimal .tpl-logo {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .tpl-minimal .tpl-logo img {
            max-height: 48px;
            max-width: 160px;
            object-fit: contain;
        }
        .tpl-minimal .tpl-header-text {
            font-size: 18px;
            font-weight: 600;
            color: var(--tpl-primary, #3b82f6);
        }
        .tpl-minimal .tpl-qr {
            flex-shrink: 0;
        }
        .tpl-minimal .tpl-qr canvas, .tpl-minimal .tpl-qr img {
            width: 80px;
            height: 80px;
        }
        .tpl-minimal .tpl-title {
            font-size: 26px;
            font-weight: 700;
            color: #111;
            margin-bottom: 20px;
            line-height: 1.4;
        }
        .tpl-minimal .tpl-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            padding: 12px 16px;
            background: #f9fafb;
            border-radius: 8px;
            margin-bottom: 28px;
            font-size: 13px;
            color: #6b7280;
        }
        .tpl-minimal .tpl-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tpl-minimal .tpl-meta-label {
            color: #9ca3af;
        }
        .tpl-minimal .tpl-priority {
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 500;
        }
        .tpl-minimal .priority-high { background: #fef2f2; color: #dc2626; }
        .tpl-minimal .priority-medium { background: #fffbeb; color: #d97706; }
        .tpl-minimal .priority-low { background: #f0fdf4; color: #16a34a; }
        .tpl-minimal .tpl-content {
            color: #374151;
            line-height: 2;
            margin-bottom: 40px;
            font-size: 15px;
        }
        .tpl-minimal .tpl-content p {
            margin-bottom: 12px;
            text-indent: 2em;
        }
        .tpl-minimal .tpl-footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 1px solid var(--tpl-border, #e5e7eb);
            text-align: center;
            font-size: 12px;
            color: #9ca3af;
            background: var(--tpl-footer-bg, transparent);
        }
        .tpl-minimal .tpl-footer a {
            color: var(--tpl-primary, #3b82f6);
            text-decoration: none;
            word-break: break-all;
        }

        /* ===== Official Document Template ===== */
        .tpl-official {
            padding: 70px 60px;
            font-family: var(--tpl-font, 'SimSun', 'Noto Sans SC', serif);
            color: var(--tpl-text, #111);
            background: var(--tpl-bg, #fff);
            font-size: var(--tpl-fontsize, 16px);
            line-height: 2;
        }
        .tpl-official .tpl-doc-head {
            text-align: center;
            padding-bottom: 24px;
            border-bottom: var(--tpl-border-style, double) 3px var(--tpl-border, #333);
            margin-bottom: 48px;
        }
        .tpl-official .tpl-logo-row {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        .tpl-official .tpl-logo-row img {
            max-height: 60px;
            max-width: 180px;
        }
        .tpl-official .tpl-header-text {
            font-size: 22px;
            font-weight: 700;
            color: var(--tpl-primary, #1e40af);
            letter-spacing: 4px;
        }
        .tpl-official .tpl-doc-num {
            font-size: 14px;
            color: #6b7280;
            margin-top: 8px;
        }
        .tpl-official .tpl-title {
            font-size: var(--tpl-title-size, 28px);
            font-weight: 700;
            text-align: var(--tpl-title-align, center);
            margin-bottom: 40px;
            line-height: 1.6;
            letter-spacing: 2px;
        }
        .tpl-official .tpl-meta {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            margin-bottom: 32px;
            border-top: 1px solid #666;
            border-bottom: 1px solid #666;
            font-size: 14px;
        }
        .tpl-official .tpl-meta-item span {
            margin-right: 8px;
            color: #666;
        }
        .tpl-official .tpl-content {
            text-indent: 2em;
            font-size: 16px;
            line-height: 2.2;
            margin-bottom: 60px;
        }
        .tpl-official .tpl-content p {
            margin-bottom: 16px;
        }
        .tpl-official .tpl-sign {
            text-align: right;
            margin-top: 80px;
            font-size: 16px;
            line-height: 2;
        }
        .tpl-official .tpl-sign .sign-author {
            margin-bottom: 8px;
        }
        .tpl-official .tpl-footer {
            margin-top: 60px;
            padding-top: 24px;
            border-top: 1px solid #ccc;
            text-align: center;
            font-size: 13px;
            color: #666;
        }
        .tpl-official .tpl-footer-info {
            display: flex;
            justify-content: space-between;
            margin-top: 16px;
            font-size: 12px;
        }
        .tpl-official .tpl-qr-box {
            float: right;
            margin-left: 24px;
            margin-bottom: 24px;
            text-align: center;
        }
        .tpl-official .tpl-qr-box canvas, .tpl-official .tpl-qr-box img {
            width: 90px;
            height: 90px;
        }
        .tpl-official .tpl-qr-box div {
            font-size: 11px;
            color: #999;
            margin-top: 4px;
        }

        /* ===== Card Template ===== */
        .tpl-card {
            padding: var(--tpl-padding, 40px 32px);
            font-family: var(--tpl-font, 'Noto Sans SC', sans-serif);
            color: var(--tpl-text, #374151);
            background: var(--tpl-bg, #f3f4f6);
            font-size: var(--tpl-fontsize, 14px);
            line-height: 1.8;
            min-height: 1100px;
        }
        .tpl-card .tpl-card-wrap {
            background: var(--tpl-card-bg, #fff);
            border-radius: var(--tpl-radius, 12px);
            border: 1px solid var(--tpl-border, #e5e7eb);
            box-shadow: var(--tpl-shadow, 0 4px 6px -1px rgba(0,0,0,0.1));
            overflow: hidden;
        }
        .tpl-card .tpl-card-header {
            background: linear-gradient(135deg, var(--tpl-primary, #8b5cf6), #6366f1);
            padding: 28px 32px;
            color: white;
        }
        .tpl-card .tpl-logo-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }
        .tpl-card .tpl-logo-row img {
            max-height: 40px;
            max-width: 140px;
            filter: brightness(0) invert(1);
        }
        .tpl-card .tpl-header-text {
            font-size: 18px;
            font-weight: 600;
        }
        .tpl-card .tpl-tag {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(255,255,255,0.2);
            border-radius: 999px;
            font-size: 12px;
            margin-bottom: 12px;
        }
        .tpl-card .tpl-title {
            font-size: 22px;
            font-weight: 700;
            line-height: 1.4;
            margin-bottom: 12px;
        }
        .tpl-card .tpl-meta-row {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            font-size: 13px;
            opacity: 0.9;
        }
        .tpl-card .tpl-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tpl-card .tpl-card-body {
            padding: 32px;
        }
        .tpl-card .tpl-content {
            color: #374151;
            line-height: 2;
            margin-bottom: 24px;
        }
        .tpl-card .tpl-content p {
            margin-bottom: 12px;
        }
        .tpl-card .tpl-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
            padding: 20px;
            background: #f9fafb;
            border-radius: 10px;
            margin-bottom: 24px;
        }
        .tpl-card .tpl-info-item {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .tpl-card .tpl-info-label {
            font-size: 12px;
            color: #9ca3af;
        }
        .tpl-card .tpl-info-value {
            font-size: 14px;
            color: #111827;
            font-weight: 500;
        }
        .tpl-card .tpl-qr-row {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 20px;
            border: 1px dashed #e5e7eb;
            border-radius: 10px;
        }
        .tpl-card .tpl-qr-row canvas, .tpl-card .tpl-qr-row img {
            width: 90px;
            height: 90px;
            flex-shrink: 0;
        }
        .tpl-card .tpl-qr-info {
            flex: 1;
            font-size: 13px;
        }
        .tpl-card .tpl-qr-info .tip {
            font-weight: 600;
            color: var(--tpl-primary, #8b5cf6);
            margin-bottom: 6px;
        }
        .tpl-card .tpl-qr-info .url {
            color: #6b7280;
            word-break: break-all;
            font-size: 12px;
        }
        .tpl-card .tpl-card-footer {
            padding: 20px 32px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 13px;
            color: #6b7280;
            background: #fafafa;
        }

        @media print {
            body { background: white; }
            .preview-toolbar { display: none !important; }
            .preview-container { max-width: 100%; margin: 0; padding: 0; }
            #print-area {
                min-height: auto;
                box-shadow: none !important;
            }
            .tpl-minimal, .tpl-official, .tpl-card {
                min-height: auto;
            }
        }

        @page {
            size: A4;
            margin: 0;
        }
    </style>
</head>
<body>
    <div class="preview-toolbar">
        <div class="toolbar-left">
            <a href="notice_detail.php?id=<?php echo $notice_id; ?>" class="back-btn">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                返回公告
            </a>
            <span class="toolbar-title">打印预览</span>
            <select class="template-select" id="templateSelect" onchange="switchTemplate(this.value)">
                <option value="">加载中...</option>
            </select>
        </div>
        <div class="toolbar-right">
            <button class="action-btn btn-print" id="printBtn" onclick="doPrint()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                打印
            </button>
            <button class="action-btn btn-pdf" id="pdfBtn" onclick="downloadPDF()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
                下载 PDF
            </button>
        </div>
    </div>

    <div class="preview-container">
        <div class="loading" id="loading">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>
            <div>正在加载打印数据...</div>
        </div>
        <div id="print-area" style="display:none;"></div>
    </div>

    <script>
    (function() {
        var NOTICE_ID = <?php echo $notice_id; ?>;
        var INITIAL_TEMPLATE_ID = <?php echo $template_id; ?>;
        var templates = [];
        var currentData = null;
        var currentTemplateId = 0;

        loadTemplates();
        loadPrintData(INITIAL_TEMPLATE_ID);

        function loadTemplates() {
            fetch('api_print_templates.php')
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        templates = res.data;
                        renderTemplateOptions();
                    }
                });
        }

        function renderTemplateOptions() {
            var sel = document.getElementById('templateSelect');
            var html = '';
            templates.forEach(function(t) {
                var suffix = t.is_default ? ' (默认)' : '';
                html += '<option value="' + t.id + '">' + t.name + suffix + '</option>';
            });
            sel.innerHTML = html;
            if (INITIAL_TEMPLATE_ID > 0) {
                sel.value = INITIAL_TEMPLATE_ID;
            } else if (templates.length > 0) {
                sel.value = templates[0].id;
            }
        }

        function loadPrintData(templateId) {
            currentTemplateId = templateId;
            var url = 'api_print_render.php?notice_id=' + NOTICE_ID;
            if (templateId > 0) url += '&template_id=' + templateId;
            document.getElementById('loading').style.display = 'block';
            document.getElementById('print-area').style.display = 'none';
            fetch(url)
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    if (res.success) {
                        currentData = res.data;
                        renderTemplate(currentData);
                        document.getElementById('loading').style.display = 'none';
                        document.getElementById('print-area').style.display = 'block';
                    } else {
                        document.getElementById('loading').innerHTML = '<div style="color:#dc2626;">加载失败：' + (res.message || '未知错误') + '</div>';
                    }
                })
                .catch(function(err) {
                    document.getElementById('loading').innerHTML = '<div style="color:#dc2626;">网络错误：' + err.message + '</div>';
                });
        }

        window.switchTemplate = function(templateId) {
            if (!templateId) return;
            loadPrintData(parseInt(templateId));
        };

        function renderTemplate(data) {
            var area = document.getElementById('print-area');
            var tpl = data.template;
            var notice = data.notice;
            var style = tpl.style || {};
            var type = tpl.template_type || 'minimal';
            var cssVars = buildCssVars(style);

            if (type === 'official') {
                area.innerHTML = renderOfficial(data, cssVars);
            } else if (type === 'card') {
                area.innerHTML = renderCard(data, cssVars);
            } else {
                area.innerHTML = renderMinimal(data, cssVars);
            }
            area.className = '';
            setTimeout(function() {
                var qrContainer = document.getElementById('qr-code');
                if (qrContainer && data.qr_data) {
                    qrContainer.innerHTML = '';
                    new QRCode(qrContainer, {
                        text: data.qr_data,
                        width: 100,
                        height: 100,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
                var qrContainer2 = document.getElementById('qr-code-2');
                if (qrContainer2 && data.qr_data) {
                    qrContainer2.innerHTML = '';
                    new QRCode(qrContainer2, {
                        text: data.qr_data,
                        width: 100,
                        height: 100,
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            }, 10);
        }

        function buildCssVars(style) {
            return [
                style.fontFamily ? '--tpl-font:' + style.fontFamily : '',
                style.fontSize ? '--tpl-fontsize:' + style.fontSize : '',
                style.textColor ? '--tpl-text:' + style.textColor : '',
                style.backgroundColor ? '--tpl-bg:' + style.backgroundColor : '',
                style.borderColor ? '--tpl-border:' + style.borderColor : '',
                style.primaryColor ? '--tpl-primary:' + style.primaryColor : '',
                style.headerBgColor ? '--tpl-header-bg:' + style.headerBgColor : '',
                style.footerBgColor ? '--tpl-footer-bg:' + style.footerBgColor : '',
                style.titleFontSize ? '--tpl-title-size:' + style.titleFontSize : '',
                style.titleAlign ? '--tpl-title-align:' + style.titleAlign : '',
                style.borderStyle ? '--tpl-border-style:' + style.borderStyle : '',
                style.cardBgColor ? '--tpl-card-bg:' + style.cardBgColor : '',
                style.borderRadius ? '--tpl-radius:' + style.borderRadius : '',
                style.shadow ? '--tpl-shadow:' + style.shadow : '',
                style.padding ? '--tpl-padding:' + style.padding : '',
            ].filter(Boolean).join(';');
        }

        function renderMinimal(data, cssVars) {
            var n = data.notice, t = data.template;
            return '<div class="tpl-minimal" style="' + cssVars + '">' +
                '<div class="tpl-header">' +
                    '<div class="tpl-logo">' +
                        (t.logo_url ? '<img src="' + escapeAttr(t.logo_url) + '" alt="logo">' : '') +
                        (t.header_text ? '<span class="tpl-header-text">' + escapeHtml(t.header_text) + '</span>' : '') +
                    '</div>' +
                    '<div class="tpl-qr" id="qr-code"></div>' +
                '</div>' +
                '<h1 class="tpl-title">' + escapeHtml(n.title) + '</h1>' +
                '<div class="tpl-meta">' +
                    '<span class="tpl-meta-item"><span class="tpl-meta-label">作者：</span>' + escapeHtml(n.author) + '</span>' +
                    '<span class="tpl-meta-item"><span class="tpl-meta-label">发布时间：</span>' + escapeHtml(n.publish_date) + '</span>' +
                    '<span class="tpl-meta-item"><span class="tpl-meta-label">优先级：</span><span class="tpl-priority priority-' + n.priority + '">' + n.priority_text + '</span></span>' +
                    '<span class="tpl-meta-item"><span class="tpl-meta-label">浏览：</span>' + n.views + '</span>' +
                '</div>' +
                '<div class="tpl-content">' + n.content_html + '</div>' +
                '<div class="tpl-footer">' +
                    (t.footer_text ? escapeHtml(t.footer_text) + '<br>' : '') +
                    '详情链接：<a href="' + escapeAttr(data.detail_url) + '">' + escapeHtml(data.detail_url) + '</a>' +
                '</div>' +
            '</div>';
        }

        function renderOfficial(data, cssVars) {
            var n = data.notice, t = data.template;
            return '<div class="tpl-official" style="' + cssVars + '">' +
                '<div class="tpl-doc-head">' +
                    '<div class="tpl-logo-row">' +
                        (t.logo_url ? '<img src="' + escapeAttr(t.logo_url) + '" alt="logo">' : '') +
                        (t.header_text ? '<span class="tpl-header-text">' + escapeHtml(t.header_text) + '</span>' : '') +
                    '</div>' +
                    '<div class="tpl-doc-num">编号：GG-' + padZero(n.id, 6) + '</div>' +
                '</div>' +
                '<h1 class="tpl-title">' + escapeHtml(n.title) + '</h1>' +
                '<div class="tpl-qr-box" id="qr-code"><div>扫码查看</div></div>' +
                '<div class="tpl-meta">' +
                    '<span class="tpl-meta-item"><span>签发人：</span>' + escapeHtml(n.author) + '</span>' +
                    '<span class="tpl-meta-item"><span>发布日期：</span>' + escapeHtml(n.publish_date_cn) + '</span>' +
                '</div>' +
                '<div class="tpl-content">' + n.content_html + '</div>' +
                '<div class="tpl-sign">' +
                    '<div class="sign-author">' + escapeHtml(n.author) + '</div>' +
                    '<div>' + escapeHtml(n.publish_date_cn) + '</div>' +
                '</div>' +
                '<div style="clear:both;"></div>' +
                '<div class="tpl-footer">' +
                    (t.footer_text ? escapeHtml(t.footer_text) + '<br>' : '') +
                    '<div class="tpl-footer-info">' +
                        '<span>本文件由系统自动生成</span>' +
                        '<span>详情：' + escapeHtml(data.detail_url) + '</span>' +
                    '</div>' +
                '</div>' +
            '</div>';
        }

        function renderCard(data, cssVars) {
            var n = data.notice, t = data.template;
            return '<div class="tpl-card" style="' + cssVars + '">' +
                '<div class="tpl-card-wrap">' +
                    '<div class="tpl-card-header">' +
                        '<div class="tpl-logo-row">' +
                            (t.logo_url ? '<img src="' + escapeAttr(t.logo_url) + '" alt="logo">' : '') +
                            (t.header_text ? '<span class="tpl-header-text">' + escapeHtml(t.header_text) + '</span>' : '') +
                        '</div>' +
                        '<span class="tpl-tag">优先级：' + n.priority_text + ' · ' + n.status_text + '</span>' +
                        '<h1 class="tpl-title">' + escapeHtml(n.title) + '</h1>' +
                        '<div class="tpl-meta-row">' +
                            '<span class="tpl-meta-item">👤 ' + escapeHtml(n.author) + '</span>' +
                            '<span class="tpl-meta-item">📅 ' + escapeHtml(n.publish_date) + '</span>' +
                            '<span class="tpl-meta-item">👁 ' + n.views + ' 浏览</span>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tpl-card-body">' +
                        '<div class="tpl-info-grid">' +
                            '<div class="tpl-info-item"><span class="tpl-info-label">公告编号</span><span class="tpl-info-value">GG-' + padZero(n.id, 6) + '</span></div>' +
                            '<div class="tpl-info-item"><span class="tpl-info-label">发布日期</span><span class="tpl-info-value">' + escapeHtml(n.publish_date_cn) + '</span></div>' +
                            '<div class="tpl-info-item"><span class="tpl-info-label">发布人</span><span class="tpl-info-value">' + escapeHtml(n.author) + '</span></div>' +
                            '<div class="tpl-info-item"><span class="tpl-info-label">优先级</span><span class="tpl-info-value">' + n.priority_text + '</span></div>' +
                        '</div>' +
                        '<div class="tpl-content">' + n.content_html + '</div>' +
                        '<div class="tpl-qr-row">' +
                            '<div id="qr-code-2"></div>' +
                            '<div class="tpl-qr-info">' +
                                '<div class="tip">📱 扫码查看详情</div>' +
                                '<div class="url">' + escapeHtml(data.detail_url) + '</div>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="tpl-card-footer">' + (t.footer_text ? escapeHtml(t.footer_text) : '') + '</div>' +
                '</div>' +
            '</div>';
        }

        function escapeHtml(str) {
            if (str == null) return '';
            return String(str).replace(/[&<>"']/g, function(c) {
                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
            });
        }
        function escapeAttr(str) { return escapeHtml(str); }
        function padZero(num, len) {
            var s = String(num);
            while (s.length < len) s = '0' + s;
            return s;
        }

        window.doPrint = function() {
            setTimeout(function() { window.print(); }, 100);
        };

        window.downloadPDF = function() {
            var btn = document.getElementById('pdfBtn');
            btn.disabled = true;
            var originalText = btn.innerHTML;
            btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 1 1-6.219-8.56"/></svg>生成中...';

            var element = document.getElementById('print-area').firstChild;
            if (!element) { btn.disabled = false; btn.innerHTML = originalText; return; }
            var opt = {
                margin: 0,
                filename: '公告_' + (currentData ? currentData.notice.id : NOTICE_ID) + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            html2pdf().set(opt).from(element).save().then(function() {
                btn.disabled = false;
                btn.innerHTML = originalText;
            }).catch(function(err) {
                alert('PDF生成失败：' + err.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        };
    })();
    </script>
</body>
</html>
