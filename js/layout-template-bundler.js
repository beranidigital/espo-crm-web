/************************************************************************
 * This file is part of EspoCRM.
 *
 * EspoCRM – Open Source CRM application.
 * Copyright (C) 2014-2024 Yurii Kuznietsov, Taras Machyshyn, Oleksii Avramenko
 * Website: https://www.espocrm.com
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 * The interactive user interfaces in modified source and object code versions
 * of this program must display Appropriate Legal Notices, as required under
 * Section 5 of the GNU Affero General Public License version 3.
 *
 * In accordance with Section 7(b) of the GNU Affero General Public License version 3,
 * these Appropriate Legal Notices must retain the display of the "EspoCRM" word.
 ************************************************************************/

const fs = require('fs');
const {globSync} = require('glob');

class LayoutTemplateBundler {

    /**
     * @return {string}
     */
    bundle() {
        let path = 'client/res/layout-types';

        /** @var {string[]} */
        let files = globSync(path + '/*.tpl')
            .map(file => file.replaceAll('\\', '/'));

        let map = {};

        files.forEach(file => {
            let name = file
                .substring(path.length + 1)
                .slice(0, -4);

            map[name] = fs.readFileSync(file, 'utf8');
        });

        let mapPart = JSON.stringify(map);

        return `\nEspo.layoutTemplates = ${mapPart};\n`;
    }
}

module.exports = LayoutTemplateBundler;
