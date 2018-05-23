{{#if readOnly}}
    {{translate value scope='Workflow' category='labels'}}
{{else}}
    <select name="subjectType" class="form-control input-sm">
        {{options list value scope='Workflow' field='subjectType'}}
    </select>
{{/if}}
