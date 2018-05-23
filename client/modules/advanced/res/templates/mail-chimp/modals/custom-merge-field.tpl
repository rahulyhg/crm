<div >
<label>{{translate 'Merge Field Tag' category='fields' scope="MailChimp"}}</label>
    <div class="field-container field" data-field="mergeFieldTag">
        <div class="row">
            <div class="cell col-sm-6 form-group">
                <input class="form-control text-uppercase" maxlength="10" name="mergeFieldTag" {{#unless editable}} disabled="disabled" {{/unless}}  value="{{{tag}}}"></input>
            </div>
        </div>
    </div>
</div>
<div >
<label>{{translate 'Merge Field Name' category='fields' scope="MailChimp"}}</label>
    <div class="field-container field" data-field="mergeFieldName">
        <div class="row">
            <div class="cell col-sm-6 form-group">
                <input class="form-control" name="mergeFieldName" maxlength="50" {{#unless editable}} disabled="disabled" {{/unless}} value="{{{name}}}"></input>
            </div>
        </div>
    </div>
</div>
<div >
<label>{{translate 'Merge Field Type' category='fields' scope="MailChimp"}}</label>
    <div class="field-container field" data-field="mergeFieldTag">
        <div class="row">
            <div class="cell col-sm-6 form-group">
                <select class="form-control" name="mergeFieldType" {{#unless editable}} disabled="disabled"{{/unless}} >{{{options typeList type}}}</select>
            </div>
        </div>
    </div>
</div>

<div class="field-definitions">
{{#each scopesData}}
    <div class="margin clearfix field-row" data-field="{{@key}}Field" style="margin-left: 20px;">
        <label class="control-label">{{translate @key category='scopeNames'}}</label>
        <div class="field-row cell form-group" data-field="{{@key}}Field">

            <div class="col-sm-6 subject-field">

                <select class="form-control" name="{{@key}}Field">
                <option value=""> {{translate 'Skip' category='labels' scope="MailChimp"}}</option>
                {{#each ./this}}
                    <option value="{{this}}"> {{translate this category='fields' scope=@../key}}</option>
                {{/each}}
                </select>
            </div>
        </div>
    </div>
{{/each}}

</div>
