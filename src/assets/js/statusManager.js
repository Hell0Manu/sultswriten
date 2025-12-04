(function($){
  'use strict';

  $(document).ready(function () {
    const workflowData = window.SultsWritenStatuses || {};
    const roles = workflowData.current_roles || [];
    const allowed = workflowData.allowed_roles || [];
    const statuses = workflowData.statuses || {};

    const hasPermission = roles.some(r => allowed.includes(r));
    if (!hasPermission) {
      return;
    }

    const $classicSelect = $("select#post_status");
    if ($classicSelect.length) {
      Object.keys(statuses).forEach(slug => {
        if ($classicSelect.find(`option[value="${slug}"]`).length === 0) {
          $classicSelect.append(`<option value="${slug}">${statuses[slug]}</option>`);
        }
      });
      const currentVal = $classicSelect.val();
      if (statuses[currentVal]) {
        $("#post-status-display").text(" " + statuses[currentVal]);
      }
    }

    $(".inline-edit-status select").each(function () {
      const $quickSelect = $(this);

      Object.keys(statuses).forEach(slug => {
        if ($quickSelect.find(`option[value="${slug}"]`).length === 0) {
          $quickSelect.append(`<option value="${slug}">${statuses[slug]}</option>`);
        }
      });
    });

  });

})(jQuery);