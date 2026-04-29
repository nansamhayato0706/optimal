/**
 * month-picker.js
 * モダンな月選択ピッカー（再利用可能）
 *
 * 使い方:
 *   月を格納 → class="month-picker"
 *   終了月を格納 → class="month-picker month-picker-end"
 *
 * 格納フォーマット: YYYY-MM
 *
 * 依存: jQuery
 */
(function ($) {
    'use strict';

    var MonthPicker = {

        MONTHS: ['1月', '2月', '3月', '4月', '5月', '6月',
                 '7月', '8月', '9月', '10月', '11月', '12月'],
        MIN_YEAR: 2016,
        MAX_YEAR: new Date().getFullYear() + 1,

        _year: new Date().getFullYear(),
        _$input: null,
        _$popup: null,

        /**
         * 初期化（自動で呼ばれる。手動で呼ぶことも可能）
         * @param {string} [selector='.month-picker'] - 対象セレクター
         */
        init: function (selector) {
            var self = this;
            selector = selector || '.month-picker';

            self._buildPopup();

            // input クリックでポップアップ表示
            $(document).on('click.monthpicker', selector, function (e) {
                e.stopPropagation();
                var $this = $(this);
                // 同じ input をもう一度クリック → 閉じる
                if (self._$input && self._$input[0] === this && self._$popup.hasClass('mp-open')) {
                    self._close();
                    return;
                }
                self._$input = $this;
                self._open();
            });

            // 外側クリックで閉じる
            $(document).on('click.monthpicker', function () {
                self._close();
            });

            // ESC で閉じる
            $(document).on('keydown.monthpicker', function (e) {
                if (e.key === 'Escape') {
                    self._close();
                }
            });
        },

        // ----------------------------------------------------------------
        // Private
        // ----------------------------------------------------------------

        _buildPopup: function () {
            var self = this;

            var html = [
                '<div id="mp-popup" class="mp-popup" role="dialog" aria-modal="true" aria-label="月を選択">',
                '  <div class="mp-header">',
                '    <button class="mp-nav mp-prev" type="button" aria-label="前の年">&#8249;</button>',
                '    <select class="mp-year-select" aria-label="西暦を選択"></select>',
                '    <button class="mp-nav mp-next" type="button" aria-label="次の年">&#8250;</button>',
                '  </div>',
                '  <div class="mp-grid"></div>',
                '</div>'
            ].join('');

            $('body').append(html);
            self._$popup = $('#mp-popup');

            // ポップアップ内クリックは外側クリック判定を止める
            self._$popup.on('click.monthpicker', function (e) {
                e.stopPropagation();
            });

            // 前年
            self._$popup.on('click.monthpicker', '.mp-prev', function () {
                if (self._year > self.MIN_YEAR) {
                    self._year--;
                    self._render();
                }
            });

            // 次年
            self._$popup.on('click.monthpicker', '.mp-next', function () {
                if (self._year < self.MAX_YEAR) {
                    self._year++;
                    self._render();
                }
            });

            // 西暦選択
            self._$popup.on('change.monthpicker', '.mp-year-select', function () {
                self._year = parseInt($(this).val(), 10);
                self._render();
            });

            // 月ボタン選択
            self._$popup.on('click.monthpicker', '.mp-month', function () {
                var month = parseInt($(this).data('m'), 10);
                self._select(month);
            });
        },

        _open: function () {
            var self = this;

            // 既存の値から年を読み取る（YYYY-MM / YYYY-MM-DD どちらも対応）
            var val = self._$input.val();
            if (val && /^\d{4}-\d{2}/.test(val)) {
                self._year = parseInt(val.substring(0, 4), 10);
            } else {
                self._year = new Date().getFullYear();
            }

            self._render();
            self._position();
            self._$popup.addClass('mp-open');
            self._$input.attr('aria-expanded', 'true');
        },

        _close: function () {
            if (this._$popup) {
                this._$popup.removeClass('mp-open');
            }
            if (this._$input) {
                this._$input.attr('aria-expanded', 'false');
            }
            this._$input = null;
        },

        _render: function () {
            var self = this;
            var today = new Date();
            var thisYear = today.getFullYear();
            var thisMonth = today.getMonth() + 1; // 1-12

            // 現在選択中の年月を取得（YYYY-MM / YYYY-MM-DD どちらも対応）
            var selectedYear = 0, selectedMonth = 0;
            if (self._$input) {
                var val = self._$input.val();
                if (val && /^\d{4}-\d{2}/.test(val)) {
                    selectedYear = parseInt(val.substring(0, 4), 10);
                    selectedMonth = parseInt(val.substring(5, 7), 10);
                }
            }

            // 年セレクト
            var yearOptions = '';
            for (var year = self.MIN_YEAR; year <= self.MAX_YEAR; year++) {
                yearOptions += '<option value="' + year + '"'
                    + (year === self._year ? ' selected' : '')
                    + '>' + year + '年</option>';
            }
            self._$popup.find('.mp-year-select').html(yearOptions);

            // ナビボタンの有効・無効
            self._$popup.find('.mp-prev').prop('disabled', self._year <= self.MIN_YEAR);
            self._$popup.find('.mp-next').prop('disabled', self._year >= self.MAX_YEAR);

            // 月ボタン生成
            var cells = '';
            for (var i = 1; i <= 12; i++) {
                var cls = 'mp-month';
                if (selectedYear === self._year && selectedMonth === i) {
                    cls += ' mp-selected';
                }
                if (thisYear === self._year && thisMonth === i) {
                    cls += ' mp-today';
                }
                cells += '<button class="' + cls + '" data-m="' + i + '" type="button">'
                       + self.MONTHS[i - 1]
                       + '</button>';
            }
            self._$popup.find('.mp-grid').html(cells);
        },

        _position: function () {
            var self = this;
            var offset = self._$input.offset();
            var inputH = self._$input.outerHeight();
            var popupH = self._$popup.outerHeight(true);
            var winH   = $(window).height();
            var scrollY = $(window).scrollTop();

            // デフォルトは input の下
            var top = offset.top + inputH + 6;

            // 画面下がはみ出し、かつ上に十分スペースがある場合は上向きに
            if (top + popupH > scrollY + winH && offset.top - popupH - 6 > scrollY) {
                top = offset.top - popupH - 6;
            }

            self._$popup.css({
                top:  top,
                left: offset.left
            });
        },

        _select: function (month) {
            var self = this;
            if (!self._$input) return;

            var mm  = String(month).padStart(2, '0');
            var fmt = self._$input.data('mpFormat') || '';
            var val;

            val = self._year + '-' + mm;

            self._$input.val(val).trigger('change');
            self._close();
        }
    };

    // DOM 準備完了後に自動初期化（.month-picker が存在する場合のみ）
    $(function () {
        if ($('.month-picker').length > 0) {
            MonthPicker.init();
        }
    });

    // 外部から呼べるように公開
    window.MonthPicker = MonthPicker;

})(jQuery);
