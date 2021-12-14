/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2017 (original work) Open Assessment Technologies SA;
 *
 *
 */

/**
 * This code is part of the theme-preview.svg
 *
 * @author Dieter Raber <dieter@taotesting.com>
 */
(function() {
    var params = document.defaultView.frameElement.getElementsByTagName('param');

    var colorMap  = (function() {
        var i, l = params.length,
            cMap = new Map(),
            re = /^(rgb|hsb|hsl|#)/;
        for (i = 0; i < l; i++) {
            if (re.test(params[i].value)) {
                cMap.set(params[i].name, params[i].value);
            }
        }
        return cMap;
    }());

    // map of selectors
    var selectorMap = (function() {
        var map = new Map();

        if (!colorMap.size) {
            return map;
        }
        map.set('.svg-dark-bar', 'mainBarBg');
        map.set('.svg-footer-fg, .svg-menu-fg', 'mainBarText');
        map.set('.svg-menu-active-bg', 'menuActiveBg');
        map.set('.svg-menu-active-fg', 'menuActiveText');
        map.set('.svg-action-bar', 'actionBarBg');
        map.set('.svg-action-fg', 'actionBarText');
        map.set('.svg-menu-bg:hover', 'menuActiveBg');
        return map;
    }());

    selectorMap.forEach(function(scssKey, selector) {
        var hover = selector.match(/([^\:]+)\:(hover)/),
            color = colorMap.get(scssKey),
            nodes;
        selector = hover !== null ? hover[1] : selector;
        nodes = document.querySelectorAll(selector);
        [].forEach.call(nodes, function(node) {
            var oldFill;
            if (!hover) {
                node.style.fill = color;
            } else {
                oldFill = node.style.fill;
                node.addEventListener('mouseover', function() {
                    this.style.fill = color;
                });
                node.addEventListener('mouseout', function() {
                    this.style.fill = oldFill;
                });
            }
        });
    });
}());
