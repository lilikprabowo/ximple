<?php
$sn_v = file_exists(FCPATH . 'assets/js/summernote/summernote.min.js') ? filemtime(FCPATH . 'assets/js/summernote/summernote.min.js') : '4.1';
?>
<link rel="stylesheet" type="text/css" href="<?php echo base_url('assets/js/summernote/summernote.css?v=' . $sn_v); ?>" />
<script type="text/javascript" src="<?php echo base_url('assets/js/summernote/summernote.min.js?v=' . $sn_v); ?>"></script>
<script type="text/javascript" src="<?php echo base_url('assets/js/summernote/lang/summernote-' . lang('language_locale_long') . '.js?v=' . $sn_v); ?>"></script>
<script type="text/javascript">
(function () {
    if (window._rise4TableResizeInit) return;
    window._rise4TableResizeInit = true;

    var resizing = null;

    function getCellEdge(e, cell) {
        var rect = cell.getBoundingClientRect();
        return (rect.right - e.clientX) <= 7;
    }

    document.addEventListener("mousemove", function (e) {
        if (resizing) {
            var delta = e.clientX - resizing.startX;
            var newW = Math.max(40, resizing.startW + delta);
            resizing.table.style.tableLayout = "fixed";
            resizing.table.style.width = "auto";
            // Apply width to all cells in the same column index
            var rows = resizing.table.rows;
            for (var r = 0; r < rows.length; r++) {
                var c = rows[r].cells[resizing.colIdx];
                if (c) {
                    c.style.width = newW + "px";
                }
            }
            e.preventDefault();
            return;
        }

        var cell = e.target && e.target.closest ? e.target.closest(".note-editable td, .note-editable th") : null;
        if (cell) {
            cell.style.cursor = getCellEdge(e, cell) ? "col-resize" : "";
        }
    });

    document.addEventListener("mousedown", function (e) {
        var cell = e.target && e.target.closest ? e.target.closest(".note-editable td, .note-editable th") : null;
        if (!cell || !getCellEdge(e, cell)) return;

        var table = cell.closest("table");
        if (!table) return;

        // Lock current column widths before resizing so sibling columns stay stable
        var firstRow = table.rows[0];
        if (firstRow) {
            for (var i = 0; i < firstRow.cells.length; i++) {
                var w = firstRow.cells[i].getBoundingClientRect().width;
                for (var r = 0; r < table.rows.length; r++) {
                    if (table.rows[r].cells[i]) {
                        table.rows[r].cells[i].style.width = Math.round(w) + "px";
                    }
                }
            }
        }

        resizing = {
            table: table,
            colIdx: cell.cellIndex,
            startX: e.clientX,
            startW: cell.getBoundingClientRect().width
        };
        document.body.classList.add("rise4-col-resizing");
        e.preventDefault();
    });

    document.addEventListener("mouseup", function () {
        if (!resizing) return;
        resizing = null;
        document.body.classList.remove("rise4-col-resizing");
    });

    // Inline Table Quick-Actions Bar inside each .note-editor (appears automatically whenever a table exists)
    var activeCellByEditor = new WeakMap();

    function getOrCreateEditorBar(editor) {
        var bar = editor.querySelector(".rise4-table-action-bar");
        if (bar) return bar;

        bar = document.createElement("div");
        bar.className = "rise4-table-action-bar";
        bar.style.display = "none";
        bar.innerHTML =
            '<span class="rise4-tbl-label">Tabel:</span>' +
            '<button type="button" data-act="add-row" title="Tambah Baris">+ Baris</button>' +
            '<button type="button" data-act="del-row" title="Hapus Baris">&minus; Baris</button>' +
            '<span class="rise4-tbl-sep"></span>' +
            '<button type="button" data-act="add-col" title="Tambah Kolom">+ Kolom</button>' +
            '<button type="button" data-act="del-col" title="Hapus Kolom">&minus; Kolom</button>' +
            '<span class="rise4-tbl-sep"></span>' +
            '<button type="button" data-act="merge-right" title="Gabungkan dengan sel kanan">Merge &rarr;</button>' +
            '<button type="button" data-act="merge-down" title="Gabungkan dengan sel bawah">Merge &darr;</button>' +
            '<button type="button" data-act="split-cell" title="Pisahkan sel yang digabung">Split</button>' +
            '<span class="rise4-tbl-sep"></span>' +
            '<button type="button" data-act="del-table" class="danger" title="Hapus Tabel"><i class="ti ti-trash"></i> Hapus Tabel</button>';

        var toolbar = editor.querySelector(".note-toolbar");
        if (toolbar && toolbar.parentNode) {
            toolbar.parentNode.insertBefore(bar, toolbar.nextSibling);
        } else {
            editor.insertBefore(bar, editor.firstChild);
        }

        bar.addEventListener("mousedown", function (e) {
            e.preventDefault();
        });

        bar.addEventListener("click", function (e) {
            var btn = e.target && e.target.closest ? e.target.closest("button[data-act]") : null;
            if (!btn) return;
            var editable = editor.querySelector(".note-editable");
            if (!editable) return;

            var cell = activeCellByEditor.get(editor);
            if (!cell || !editable.contains(cell)) {
                cell = editable.querySelector("td, th");
            }
            var table = cell ? cell.closest("table") : editable.querySelector("table");
            if (!table) {
                refreshEditorBar(editor);
                return;
            }

            var act = btn.getAttribute("data-act");
            var row = cell ? cell.parentElement : table.rows[table.rows.length - 1];
            var rowIdx = row ? row.rowIndex : 0;
            var colIdx = cell ? cell.cellIndex : 0;

            if (act === "add-row") {
                var newRow = table.insertRow(rowIdx + 1);
                var cols = row ? row.cells.length : 1;
                for (var c = 0; c < cols; c++) {
                    var td = newRow.insertCell(c);
                    td.innerHTML = "<br>";
                }
            } else if (act === "del-row") {
                if (table.rows.length <= 1) {
                    table.parentNode.removeChild(table);
                } else {
                    table.deleteRow(rowIdx);
                }
            } else if (act === "add-col") {
                for (var r = 0; r < table.rows.length; r++) {
                    var newCell = table.rows[r].insertCell(colIdx + 1);
                    newCell.innerHTML = "<br>";
                }
            } else if (act === "del-col") {
                var totalCols = row ? row.cells.length : 1;
                if (totalCols <= 1) {
                    table.parentNode.removeChild(table);
                } else {
                    for (var r2 = 0; r2 < table.rows.length; r2++) {
                        if (table.rows[r2].cells[colIdx]) {
                            table.rows[r2].deleteCell(colIdx);
                        }
                    }
                }
            } else if (act === "merge-right") {
                if (cell && cell.nextElementSibling) {
                    var rightCell = cell.nextElementSibling;
                    if ((cell.rowSpan || 1) === (rightCell.rowSpan || 1)) {
                        var rightText = (rightCell.innerHTML || "").replace(/<br\s*\/?>/gi, "").trim();
                        if (rightText) {
                            var curText = (cell.innerHTML || "").replace(/<br\s*\/?>/gi, "").trim();
                            cell.innerHTML = curText ? (curText + " " + rightCell.innerHTML) : rightCell.innerHTML;
                        }
                        cell.colSpan = (cell.colSpan || 1) + (rightCell.colSpan || 1);
                        rightCell.parentNode.removeChild(rightCell);
                    }
                }
            } else if (act === "merge-down") {
                if (cell) {
                    var grid = buildTableGrid(table);
                    var pos = findCellPos(grid, cell);
                    if (pos) {
                        var targetRowIdx = pos.r + (cell.rowSpan || 1);
                        if (grid[targetRowIdx] && grid[targetRowIdx][pos.c]) {
                            var downCell = grid[targetRowIdx][pos.c];
                            if (downCell !== cell && (downCell.colSpan || 1) === (cell.colSpan || 1)) {
                                var downText = (downCell.innerHTML || "").replace(/<br\s*\/?>/gi, "").trim();
                                if (downText) {
                                    var curText2 = (cell.innerHTML || "").replace(/<br\s*\/?>/gi, "").trim();
                                    cell.innerHTML = curText2 ? (curText2 + "<br>" + downCell.innerHTML) : downCell.innerHTML;
                                }
                                cell.rowSpan = (cell.rowSpan || 1) + (downCell.rowSpan || 1);
                                downCell.parentNode.removeChild(downCell);
                            }
                        }
                    }
                }
            } else if (act === "split-cell") {
                if (cell && ((cell.colSpan || 1) > 1 || (cell.rowSpan || 1) > 1)) {
                    var origColSpan = cell.colSpan || 1;
                    var origRowSpan = cell.rowSpan || 1;
                    var sGrid = buildTableGrid(table);
                    var sPos = findCellPos(sGrid, cell);
                    cell.colSpan = 1;
                    cell.rowSpan = 1;
                    if (sPos) {
                        for (var sr = 0; sr < origRowSpan; sr++) {
                            var targetTr = table.rows[sPos.r + sr];
                            if (!targetTr) continue;
                            var neededCols = (sr === 0) ? (origColSpan - 1) : origColSpan;
                            var insertBeforeEl = null;
                            if (sr === 0) {
                                insertBeforeEl = cell.nextElementSibling;
                            } else {
                                // find first real cell in targetTr whose grid col > sPos.c
                                for (var sc = sPos.c + origColSpan; sc < (sGrid[sPos.r + sr] || []).length; sc++) {
                                    var cand = sGrid[sPos.r + sr][sc];
                                    if (cand && cand.parentElement === targetTr) {
                                        insertBeforeEl = cand;
                                        break;
                                    }
                                }
                            }
                            for (var k = 0; k < neededCols; k++) {
                                var newTd = document.createElement("td");
                                newTd.innerHTML = "<br>";
                                targetTr.insertBefore(newTd, insertBeforeEl);
                            }
                        }
                    }
                }
            } else if (act === "del-table") {
                table.parentNode.removeChild(table);
            }

            syncEditorTextarea(editor);
            refreshEditorBar(editor);
        });

        return bar;
    }

    function buildTableGrid(table) {
        var grid = [];
        for (var r = 0; r < table.rows.length; r++) {
            grid[r] = grid[r] || [];
            var cIdx = 0;
            var cells = table.rows[r].cells;
            for (var i = 0; i < cells.length; i++) {
                while (grid[r][cIdx]) cIdx++;
                var cell = cells[i];
                var rs = cell.rowSpan || 1;
                var cs = cell.colSpan || 1;
                for (var dr = 0; dr < rs; dr++) {
                    grid[r + dr] = grid[r + dr] || [];
                    for (var dc = 0; dc < cs; dc++) {
                        grid[r + dr][cIdx + dc] = cell;
                    }
                }
                cIdx += cs;
            }
        }
        return grid;
    }

    function findCellPos(grid, targetCell) {
        for (var r = 0; r < grid.length; r++) {
            for (var c = 0; c < grid[r].length; c++) {
                if (grid[r][c] === targetCell) {
                    return { r: r, c: c };
                }
            }
        }
        return null;
    }

    function syncEditorTextarea(editor) {
        var editable = editor ? editor.querySelector(".note-editable") : null;
        if (!editable) return;
        var prev = editor.previousElementSibling;
        if (prev && prev.tagName === "TEXTAREA") {
            prev.value = editable.innerHTML;
        }
    }

    // Emoji Picker button injected into every Summernote .note-toolbar
    var EMOJI_LIST = [
        "😀","😃","😄","😁","😂","🤣","😊","😉","😍","🥰",
        "😎","🤔","🤨","😐","😴","😅","😢","😭","😤","😡",
        "👍","👎","👏","🙌","🤝","🙏","💪","👌","✌️","🫡",
        "❤️","🔥","⭐","✨","🎉","🎊","✅","❌","⚠️","ℹ️",
        "📌","📎","📝","📅","⏰","🚀","💻","📊","💡","🎯"
    ];

    var savedRangeByEditor = new WeakMap();

    function saveEditorRange(editor) {
        if (!window.getSelection) return;
        var sel = window.getSelection();
        if (sel && sel.rangeCount > 0) {
            var r = sel.getRangeAt(0);
            var editable = editor.querySelector(".note-editable");
            if (editable && editable.contains(r.commonAncestorContainer)) {
                savedRangeByEditor.set(editor, r.cloneRange());
            }
        }
    }

    function insertEmojiIntoEditor(editor, emoji) {
        var editable = editor.querySelector(".note-editable");
        if (!editable) return;
        editable.focus();

        var sel = window.getSelection ? window.getSelection() : null;
        var range = savedRangeByEditor.get(editor);

        if (sel && range && editable.contains(range.commonAncestorContainer)) {
            sel.removeAllRanges();
            sel.addRange(range);
        }

        if (sel && sel.rangeCount > 0 && editable.contains(sel.getRangeAt(0).commonAncestorContainer)) {
            var cur = sel.getRangeAt(0);
            cur.deleteContents();
            var textNode = document.createTextNode(emoji);
            cur.insertNode(textNode);
            cur.setStartAfter(textNode);
            cur.setEndAfter(textNode);
            sel.removeAllRanges();
            sel.addRange(cur);
            savedRangeByEditor.set(editor, cur.cloneRange());
        } else {
            editable.appendChild(document.createTextNode(emoji));
        }

        syncEditorTextarea(editor);
    }

    function ensureEmojiButton(editor) {
        var toolbar = editor.querySelector(".note-toolbar");
        if (!toolbar || toolbar.querySelector(".rise4-emoji-group")) return;

        // Add Indent / Outdent buttons for easy nested lists (list di dalam list)
        var indentGroup = document.createElement("div");
        indentGroup.className = "note-btn-group btn-group rise4-indent-group";
        indentGroup.innerHTML =
            '<button type="button" class="note-btn btn btn-default btn-sm" data-cmd="outdent" title="Outdent / Naik Level List (Shift+Tab)"><i class="ti ti-indent-decrease"></i></button>' +
            '<button type="button" class="note-btn btn btn-default btn-sm" data-cmd="indent" title="Indent / Sub-List (Tab)"><i class="ti ti-indent-increase"></i></button>';

        indentGroup.addEventListener("mousedown", function (e) {
            e.preventDefault();
        });
        indentGroup.addEventListener("click", function (e) {
            var b = e.target && e.target.closest ? e.target.closest("button[data-cmd]") : null;
            if (!b) return;
            e.preventDefault();
            var editable = editor.querySelector(".note-editable");
            if (editable) {
                editable.focus();
                document.execCommand(b.getAttribute("data-cmd"), false, null);
                syncEditorTextarea(editor);
            }
        });
        toolbar.appendChild(indentGroup);

        var group = document.createElement("div");
        group.className = "note-btn-group btn-group rise4-emoji-group";

        var btn = document.createElement("button");
        btn.type = "button";
        btn.className = "note-btn btn btn-default btn-sm rise4-emoji-btn";
        btn.title = "Sisipkan Emoji";
        btn.innerHTML = "😊";

        var popup = document.createElement("div");
        popup.className = "rise4-emoji-popover";
        popup.style.display = "none";

        var html = "";
        for (var i = 0; i < EMOJI_LIST.length; i++) {
            html += '<button type="button" class="rise4-emoji-item" data-emoji="' + EMOJI_LIST[i] + '">' + EMOJI_LIST[i] + '</button>';
        }
        popup.innerHTML = html;

        btn.addEventListener("mousedown", function (e) {
            e.preventDefault();
            saveEditorRange(editor);
        });

        btn.addEventListener("click", function (e) {
            e.preventDefault();
            e.stopPropagation();
            var isOpen = popup.style.display === "grid";
            closeAllEmojiPopovers();
            if (!isOpen) {
                popup.style.display = "grid";
            }
        });

        popup.addEventListener("mousedown", function (e) {
            e.preventDefault();
        });

        popup.addEventListener("click", function (e) {
            var item = e.target && e.target.closest ? e.target.closest(".rise4-emoji-item") : null;
            if (!item) return;
            e.preventDefault();
            e.stopPropagation();
            insertEmojiIntoEditor(editor, item.getAttribute("data-emoji"));
            popup.style.display = "none";
        });

        group.appendChild(btn);
        group.appendChild(popup);
        toolbar.appendChild(group);
    }

    function closeAllEmojiPopovers() {
        var pops = document.querySelectorAll(".rise4-emoji-popover");
        for (var i = 0; i < pops.length; i++) {
            pops[i].style.display = "none";
        }
    }

    document.addEventListener("click", function (e) {
        if (e.target && e.target.closest && e.target.closest(".rise4-emoji-group")) return;
        closeAllEmojiPopovers();
    });

    function refreshEditorBar(editor) {
        if (!editor) return;
        ensureEmojiButton(editor);
        var editable = editor.querySelector(".note-editable");
        if (!editable) return;
        var hasTable = !!editable.querySelector("table");
        if (hasTable) {
            var bar = getOrCreateEditorBar(editor);
            bar.style.setProperty("display", "flex", "important");
        } else {
            var existing = editor.querySelector(".rise4-table-action-bar");
            if (existing) existing.style.setProperty("display", "none", "important");
        }
    }

    function refreshAllEditors() {
        var editors = document.querySelectorAll(".note-editor");
        for (var i = 0; i < editors.length; i++) {
            refreshEditorBar(editors[i]);
        }
    }

    document.addEventListener("mouseup", function (e) {
        var el = e.target && e.target.nodeType === 3 ? e.target.parentElement : e.target;
        var editor = el && el.closest ? el.closest(".note-editor") : null;
        if (editor) {
            var cell = el.closest(".note-editable td, .note-editable th");
            if (cell) {
                activeCellByEditor.set(editor, cell);
            }
            setTimeout(function () { refreshEditorBar(editor); }, 40);
        }
    }, true);

    document.addEventListener("keyup", function (e) {
        var el = e.target && e.target.nodeType === 3 ? e.target.parentElement : e.target;
        var editor = el && el.closest ? el.closest(".note-editor") : null;
        if (editor) {
            if (window.getSelection && window.getSelection().anchorNode) {
                var n = window.getSelection().anchorNode;
                if (n.nodeType === 3) n = n.parentElement;
                var cell = n && n.closest ? n.closest(".note-editable td, .note-editable th") : null;
                if (cell) activeCellByEditor.set(editor, cell);
            }
            refreshEditorBar(editor);
        }
    }, true);

    // Periodically sync bars for newly opened modals or dynamically loaded tables
    setInterval(refreshAllEditors, 800);
})();
</script>

