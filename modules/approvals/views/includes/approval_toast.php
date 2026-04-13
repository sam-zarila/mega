<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<style>
#approval-toast-container {
    position: fixed;
    right: 24px;
    bottom: 24px;
    z-index: 10050;
    max-width: 380px;
    font-family: inherit;
    pointer-events: none;
}
.approval-toast {
    pointer-events: auto;
    background: #1f2937;
    color: #f9fafb;
    border-radius: 8px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.35);
    padding: 14px 16px;
    margin-top: 12px;
    opacity: 0;
    transform: translateY(12px);
    transition: opacity 0.25s ease, transform 0.25s ease;
}
.approval-toast.approval-toast--show {
    opacity: 1;
    transform: translateY(0);
}
.approval-toast__row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
}
.approval-toast__body {
    flex: 1;
    min-width: 0;
}
.approval-toast__title {
    font-weight: 600;
    font-size: 14px;
    margin: 0 0 6px;
    line-height: 1.3;
}
.approval-toast__meta {
    font-size: 12px;
    opacity: 0.85;
    margin: 0;
    word-break: break-word;
}
.approval-toast__badges {
    margin-bottom: 8px;
}
.approval-toast__type {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.03em;
    padding: 3px 8px;
    border-radius: 4px;
    color: #fff;
}
.approval-toast__type--quotation { background: #337ab7; }
.approval-toast__type--credit_note { background: #6f42c1; }
.approval-toast__type--journal_entry { background: #e67e22; }
.approval-toast__type--payment { background: #17a2b8; }
.approval-toast__type--purchase_requisition { background: #795548; }
.approval-toast__type--default { background: #6b7280; }
.approval-toast__btn {
    display: inline-block;
    margin-top: 10px;
    padding: 6px 14px;
    font-size: 13px;
    font-weight: 600;
    border-radius: 4px;
    background: #3b82f6;
    color: #fff !important;
    text-decoration: none !important;
    border: none;
    cursor: pointer;
}
.approval-toast__btn:hover {
    background: #2563eb;
    color: #fff !important;
}
.approval-toast__close {
    background: transparent;
    border: none;
    color: #9ca3af;
    font-size: 18px;
    line-height: 1;
    padding: 0 4px;
    cursor: pointer;
}
.approval-toast__close:hover {
    color: #e5e7eb;
}
</style>
<div id="approval-toast-container" aria-live="polite" aria-relevant="additions"></div>
