

Core.define('views/import/step1', 'view', function (Dep) {

    return Dep.extend({

        template: 'import/step-1',

        events: {
            'change #import-file': function (e) {
                var $file = $(e.currentTarget);
                var files = e.currentTarget.files;
                if (files.length) {
                    this.loadFile(files[0]);
                }
            },

            'change #import-field-delimiter': function (e) {
                this.formData.fieldDelimiter = e.currentTarget.value;
                this.preview();
            },

            'change #import-text-qualifier': function (e) {
                this.formData.textQualifier = e.currentTarget.value;
                this.preview();
            },

            'click button[data-action="next"]': function () {
                this.next();
            }
        },

        getEntityList: function () {
            var list = [];
            var scopes = this.getMetadata().get('scopes');
            for (var scopeName in scopes) {
                if (scopes[scopeName].importable) {
                    list.push(scopeName);
                }
            }
            list.sort(function (v1, v2) {
                 return this.translate(v1, 'scopeNamesPlural').localeCompare(this.translate(v2, 'scopeNamesPlural'));
            }.bind(this));
            return list;
        },

        data: function () {
            return {
                entityList: this.getEntityList(),
                currencyList: this.getConfig().get('currencyList'),
                dateFormatDataList: [
                    {key: "YYYY-MM-DD", value: '2017-12-27'},
                    {key: "DD-MM-YYYY", value: '27-12-2017'},
                    {key: "MM-DD-YYYY", value: '12-27-2017'},
                    {key: "MM/DD/YYYY", value: '12/27/2017'},
                    {key: "DD/MM/YYYY", value: '27/12/2017'},
                    {key: "DD.MM.YYYY", value: '27.12.2017'},
                    {key: "MM.DD.YYYY", value: '12.27.2017'},
                    {key: "YYYY.MM.DD", value: '2017.12.27'}
                ],
                timeFormatDataList: [
                    {key: "HH:mm", value: '23:00'},
                    {key: "HH:mm:ss", value: '23:00:00'},
                    {key: "hh:mm a", value: '11:00 pm'},
                    {key: "hh:mma", value: '11:00pm'},
                    {key: "hh:mm A", value: '11:00 PM'},
                    {key: "hh:mmA", value: '11:00PM'}
                ],
                timezoneList: this.getMetadata().get(['entityDefs', 'Settings', 'fields', 'timeZone', 'options'])
            };
        },

        setup: function () {
            this.formData = this.options.formData || {
                entityType: this.options.entityType || false,
                headerRow: true,
                fieldDelimiter: ',',
                textQualifier: '"',
                dateFormat: 'YYYY-MM-DD',
                timeFormat: 'HH:mm',
                timezone: 'UTC',
                decimalMark: '.',
                personNameFormat: 'f l',
            };
        },

        afterRender: function () {
            this.setupFormData();
            if (this.getParentView() && this.getParentView().fileContents) {
                this.setFileIsLoaded();
                this.preview();
            }
        },

        next: function () {
            this.formData.headerRow = $('#import-header-row').get(0).checked;
            this.formData.entityType = $('#import-entity-type').val();
            this.formData.action = $('#import-action').val();
            this.formData.fieldDelimiter = $('#import-field-delimiter').val();
            this.formData.textQualifier = $('#import-text-qualifier').val();
            this.formData.dateFormat = $('#import-date-format').val();
            this.formData.timeFormat = $('#import-time-format').val();
            this.formData.timezone = $('#import-timezone').val();
            this.formData.decimalMark = $('#import-decimal-mark').val();
            this.formData.currency = $('#import-currency').val();
            this.formData.personNameFormat = $('#import-person-name-format').val();
            this.formData.skipDuplicateChecking = $('#skip-duplicate-checking').get(0).checked;
            this.formData.idleMode = $('#import-idle-mode').get(0).checked;

            this.getParentView().formData = this.formData;
            this.getParentView().changeStep(2);
        },

        setupFormData: function () {
            $('#import-header-row').get(0).checked = this.formData.headerRow || false;

            $('#import-idle-mode').get(0).checked = this.formData.idleMode || false;

            $('#skip-duplicate-checking').get(0).checked = this.formData.skipDuplicateChecking || false;

            if (this.formData.entityType) {
                $('#import-entity-type').val(this.formData.entityType);
            }
            if (this.formData.action) {
                $('#import-action').val(this.formData.action);
            }

            $('#import-field-delimiter').val(this.formData.fieldDelimiter);
            $('#import-text-qualifier').val(this.formData.textQualifier);
            $('#import-date-format').val(this.formData.dateFormat);
            $('#import-time-format').val(this.formData.timeFormat);
            $('#import-timezone').val(this.formData.timezone);
            $('#import-decimal-mark').val(this.formData.decimalMark);
            $('#import-person-name-format').val(this.formData.personNameFormat);

            if (this.formData.currency) {
                $('#import-currency').val(this.formData.currency);
            }
        },

        loadFile: function (file) {
            var blob = file.slice(0, 1024 * 16);

            var readerPreview = new FileReader();
            readerPreview.onloadend = function (e) {
                if (e.target.readyState == FileReader.DONE) {
                    this.formData.previewString = e.target.result;
                    this.preview();
                }
            }.bind(this);
            readerPreview.readAsText(blob);

            var reader = new FileReader();
            reader.onloadend = function (e) {
                if (e.target.readyState == FileReader.DONE) {
                    this.getParentView().fileContents = e.target.result;
                    this.setFileIsLoaded();
                }
            }.bind(this);
            reader.readAsText(file);
        },

        setFileIsLoaded: function () {
            this.$el.find('button[data-action="next"]').removeClass('hidden');
        },

        preview: function () {
            if (!this.formData.previewString) {
                return;
            }
            var arr = this.csvToArray(this.formData.previewString, this.formData.fieldDelimiter, this.formData.textQualifier);

            this.formData.previewArray = arr;

            var $table = $('<table>').addClass('table').addClass('table-bordered');

            arr.forEach(function (row, i) {
                if (i >= 3) {
                    return;
                }
                $row = $('<tr>');
                row.forEach(function (value) {
                    $cell = $('<td>').html(value);
                    $row.append($cell);
                });

                $table.append($row);
            });

            var $container = $('#import-preview');
            $container.empty().append($table);
        },

        csvToArray: function (strData, strDelimiter, strQualifier) {
            strDelimiter = (strDelimiter || ',');
            strQualifier = (strQualifier || '\"');

            var objPattern = new RegExp(
                (
                    // Delimiters.
                    "(\\" + strDelimiter + "|\\r?\\n|\\r|^)" +

                    // Quoted fields.
                    "(?:"+strQualifier+"([^"+strQualifier+"]*(?:"+strQualifier+""+strQualifier+"[^"+strQualifier+"]*)*)"+strQualifier+"|" +

                    // Standard fields.
                    "([^"+strQualifier+"\\" + strDelimiter + "\\r\\n]*))"
                ),
                "gi"
            );

            var arrData = [[]];
            var arrMatches = null;

            while (arrMatches = objPattern.exec(strData)) {
                var strMatchedDelimiter = arrMatches[1];

                if (
                    strMatchedDelimiter.length &&
                    (strMatchedDelimiter != strDelimiter)
                    ) {
                    arrData.push([]);
                }

                if (arrMatches[2]) {
                    var strMatchedValue = arrMatches[2].replace(
                            new RegExp( "\"\"", "g" ),
                            "\""
                        );
                } else {
                    var strMatchedValue = arrMatches[3];
                }

                arrData[arrData.length - 1].push(strMatchedValue);
            }

            return arrData;
        }

    });
});
