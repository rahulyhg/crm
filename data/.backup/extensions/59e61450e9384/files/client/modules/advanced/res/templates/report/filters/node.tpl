<div class="button-container">
<a class="small" style="margin-right: 10px" data-action="addField" href="javascript:">{{translate 'Add Field' scope='Report'}}</a>
<a class="small" style="margin-right: 10px" data-action="addOr" href="javascript:">{{translate 'Add OR' scope='Report'}}</a>
<a class="small" style="margin-right: 10px" data-action="addAnd" href="javascript:">{{translate 'Add AND' scope='Report'}}</a>
{{#unless notDisabled}}
<a class="small" style="margin-right: 10px" data-action="addNot" href="javascript:">{{translate 'Add NOT' scope='Report'}}</a>
{{/unless}}
{{#unless complexExpressionDisabled}}
<a class="small" style="margin-right: 10px" data-action="addComplexExpression" href="javascript:">{{translate 'Add Complex Expression' scope='Report'}}</a>
{{/unless}}
</div>

<div class="item-list"></div>