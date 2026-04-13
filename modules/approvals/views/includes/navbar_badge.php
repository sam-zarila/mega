<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php
/** @var string $approvals_pending_count_url */
/** @var string $approvals_view_base_url */
/** @var array $i18n */
$this->load->view('approvals/includes/approval_toast');
$config = [
    'pendingUrl'    => $approvals_pending_count_url,
    'viewBase'      => $approvals_view_base_url,
    'pollMs'        => 90000,
    'storageIdsKey' => 'ipms_approvals_notified_request_ids_v1',
    'sessInitKey'   => 'ipms_approvals_badge_init_v1',
    'sessLatestKey' => 'ipms_approvals_prev_latest_id_v1',
    'i18n'          => $i18n,
];
?>
<script>
(function() {
    'use strict';
    var CFG = <?= json_encode($config, JSON_UNESCAPED_SLASHES); ?>;
    var DOC_LABELS = {
        quotation: 'Quotation',
        credit_note: 'Credit note',
        journal_entry: 'Journal',
        payment: 'Payment',
        purchase_requisition: 'Purchase req.'
    };

    function stripTitleCount(title) {
        return String(title || '').replace(/^\(\d+\)\s+/, '');
    }

    var baseTitle = stripTitleCount(document.title);

    function getNotifiedIds() {
        try {
            var raw = localStorage.getItem(CFG.storageIdsKey);
            var a = raw ? JSON.parse(raw) : [];
            return Array.isArray(a) ? a : [];
        } catch (e) {
            return [];
        }
    }

    function rememberNotifiedId(id) {
        var n = String(id);
        var a = getNotifiedIds();
        if (a.indexOf(n) !== -1) {
            return;
        }
        a.push(n);
        if (a.length > 200) {
            a = a.slice(-200);
        }
        try {
            localStorage.setItem(CFG.storageIdsKey, JSON.stringify(a));
        } catch (e) {}
    }

    function alreadyNotified(id) {
        return getNotifiedIds().indexOf(String(id)) !== -1;
    }

    function updateSidebarBadge(count) {
        var li = document.querySelector('.menu-item-ipms-approvals');
        if (!li) {
            return;
        }
        var a = li.querySelector('a');
        if (!a) {
            return;
        }
        var badges = a.querySelectorAll('.badge');
        for (var i = 0; i < badges.length; i++) {
            badges[i].parentNode.removeChild(badges[i]);
        }
        if (count > 0) {
            var span = document.createElement('span');
            span.className = 'badge pull-right bg-danger';
            span.textContent = String(count);
            a.appendChild(span);
        }
    }

    function setDocumentTitleCount(count) {
        document.title = (count > 0 ? '(' + count + ') ' : '') + baseTitle;
    }

    function docTypeClass(dt) {
        var d = String(dt || '').replace(/[^a-z_]/g, '');
        return 'approval-toast__type--' + (d || 'default');
    }

    function docTypeLabel(dt) {
        return DOC_LABELS[dt] || dt.replace(/_/g, ' ') || 'Approval';
    }

    function showToast(latest) {
        var mount = document.getElementById('approval-toast-container');
        if (!mount || !latest) {
            return;
        }
        var el = document.createElement('div');
        el.className = 'approval-toast';
        el.setAttribute('role', 'alert');
        var ref = latest.document_ref || latest.request_ref || '';
        var typeLabel = docTypeLabel(latest.document_type);
        var viewUrl = CFG.viewBase + encodeURIComponent(latest.id);

        el.innerHTML =
            '<div class="approval-toast__row">' +
            '<div class="approval-toast__body">' +
            '<div class="approval-toast__badges">' +
            '<span class="approval-toast__type ' + docTypeClass(latest.document_type) + '">' +
            escapeHtml(typeLabel) + '</span></div>' +
            '<p class="approval-toast__title">' + escapeHtml(CFG.i18n.toast_new) + '</p>' +
            '<p class="approval-toast__meta">' + escapeHtml(ref) + '</p>' +
            '<a class="approval-toast__btn" href="' + escapeAttr(viewUrl) + '">' +
            escapeHtml(CFG.i18n.toast_view) + '</a>' +
            '</div>' +
            '<button type="button" class="approval-toast__close" aria-label="Close">&times;</button>' +
            '</div>';

        var closeBtn = el.querySelector('.approval-toast__close');
        closeBtn.addEventListener('click', function() {
            hideToast(el);
        });

        mount.appendChild(el);
        requestAnimationFrame(function() {
            el.classList.add('approval-toast--show');
        });
        setTimeout(function() {
            hideToast(el);
        }, 12000);
    }

    function hideToast(el) {
        if (!el || !el.parentNode) {
            return;
        }
        el.classList.remove('approval-toast--show');
        setTimeout(function() {
            if (el.parentNode) {
                el.parentNode.removeChild(el);
            }
        }, 300);
    }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    function maybeRequestNotificationPermission() {
        if (!('Notification' in window)) {
            return;
        }
        if (Notification.permission === 'default') {
            Notification.requestPermission().catch(function() {});
        }
    }

    function showBrowserNotification(latest) {
        if (!('Notification' in window) || Notification.permission !== 'granted' || !latest) {
            return;
        }
        var ref = latest.document_ref || latest.request_ref || '';
        var body = CFG.i18n.notif_body.indexOf('%s') !== -1
            ? CFG.i18n.notif_body.replace('%s', ref)
            : (CFG.i18n.notif_body + ' ' + ref);
        try {
            new Notification(CFG.i18n.notif_title, { body: body, tag: 'approval-' + latest.id });
        } catch (e) {}
    }

    function handleNewPending(latest) {
        if (!latest || !latest.id) {
            return;
        }
        if (alreadyNotified(latest.id)) {
            return;
        }
        rememberNotifiedId(latest.id);
        showBrowserNotification(latest);
        showToast(latest);
    }

    function poll() {
        fetch(CFG.pendingUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                var count = parseInt(data && data.count, 10) || 0;
                updateSidebarBadge(count);
                setDocumentTitleCount(count);

                if (count > 0) {
                    maybeRequestNotificationPermission();
                }

                var latest = data && data.latest ? data.latest : null;
                var curId = latest ? String(latest.id) : '';
                var initialized = sessionStorage.getItem(CFG.sessInitKey);
                var prevLatest = sessionStorage.getItem(CFG.sessLatestKey) || '';
                var prevCount = parseInt(sessionStorage.getItem('ipms_approvals_prev_count_v1') || '-1', 10);

                if (initialized) {
                    var countUp = prevCount >= 0 && count > prevCount;
                    var latestChanged = latest && curId !== prevLatest && curId !== '';
                    if (latest && (countUp || latestChanged)) {
                        handleNewPending(latest);
                    }
                } else {
                    sessionStorage.setItem(CFG.sessInitKey, '1');
                }

                sessionStorage.setItem(CFG.sessLatestKey, curId);
                sessionStorage.setItem('ipms_approvals_prev_count_v1', String(count));
            })
            .catch(function() {});
    }

    function start() {
        poll();
        setInterval(poll, CFG.pollMs);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', start);
    } else {
        start();
    }
})();
</script>
