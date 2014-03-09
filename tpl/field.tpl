<dl>
  <dt>
    <span class="item-title"><%= label %></span>
    <span class="item-controls">
      <span class="item-type"><%= type %></span>
      <a class="item-edit" href="#"></a>
    </span>
  </dt>
  <dd>
    <p class="description">
      <label>
        <?php _e( 'Field Label', 'wm-forms' ); ?><br />
        <input type="text" class="widefat wm-form-field-label" value="<%= label %>">
      </label>
    </p>
    <p class="description">
      <label>
        <input type="checkbox" class="wm-form-field-required"<% if (required) { %> checked<% } %>> <?php _e( 'Required', 'wm-forms' ); ?>
      </label>
    </p>
    <p class="description">
      <label>
        <?php _e( 'Type', 'wm-forms' ); ?><br />
        <select class="widefat wm-form-field-type">
          <option value="text"<% if (type === 'text') { %> selected<% } %>><?php _e( 'Text', 'wm-forms' ); ?></option>
          <option value="checkbox"<% if (type === 'checkbox') { %> selected<% } %>><?php _e( 'Checkbox', 'wm-forms' ); ?></option>
          <option value="radio"<% if (type === 'radio') { %> selected<% } %>><?php _e( 'Radio', 'wm-forms' ); ?></option>
          <option value="select"<% if (type === 'select') { %> selected<% } %>><?php _e( 'Select', 'wm-forms' ); ?></option>
          <option value="textarea"<% if (type === 'textarea') { %> selected<% } %>><?php _e( 'Textarea', 'wm-forms' ); ?></option>
          <option value="email"<% if (type === 'email') { %> selected<% } %>><?php _e( 'Email', 'wm-forms' ); ?></option>
          <option value="url"<% if (type === 'url') { %> selected<% } %>><?php _e( 'URL', 'wm-forms' ); ?></option>
        </select>
      </label>
    </p>
    <fieldset class="wm-form-options">
      <legend><?php _e( 'Options', 'wm-forms' ); ?></legend>
      <ol></ol>
      <button class="button button-small right wm-form-add-option"><?php _e( 'Add Option', 'wm-forms' ); ?></button>
    </fieldset>
    <input type="hidden" name="fid" class="wm-form-field-id" value="<%= fid %>">
    <a class="item-delete" href="#"><?php _e( 'Remove', 'wm-forms' ); ?></a>
  </dd>
</dl>
