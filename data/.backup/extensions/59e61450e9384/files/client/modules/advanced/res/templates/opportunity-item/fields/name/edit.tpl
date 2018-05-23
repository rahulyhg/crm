<div class="input-group">
    <input type="text" class="main-element form-control" name="{{name}}" {{#if isProduct}} readonly="true" {{/if}}value="{{value}}" {{#if params.maxLength}} maxlength="{{params.maxLength}}"{{/if}} autocomplete="off">
    <span class="input-group-btn">
        <button class="btn btn-default{{#if productSelectDisabled}} disabled{{/if}}" data-action="selectProduct" title="{{translate 'Select Product' scope='Opportunity'}}">
            <span class="glyphicon glyphicon-arrow-up"></span>
        </button>
    </span>
</div>