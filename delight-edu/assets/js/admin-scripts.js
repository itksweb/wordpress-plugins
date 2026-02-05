
jQuery(document).ready(function ($) {
  console.log("Dedu JS Active");

  // --- PAGINATION VARIABLES ---
  let rowsPerPage = parseInt($("#dedu-rows-per-page").val()) || 10;
  let currentPage = 1;

  // Check URL for existing page number on load
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.has("paged")) {
    currentPage = parseInt(urlParams.get("paged"));
  }

  // Helper to update URL query string without reloading
  function updateURL(page) {
    const url = new URL(window.location);
    url.searchParams.set("paged", page);
    window.history.pushState({}, "", url);
  }

  // --- 1. SELECT ALL CHECKBOXES ---
  $("#dedu-select-all").on("change", function () {
    $(".dedu-role-checkbox:visible").prop("checked", $(this).prop("checked"));
  });

  // --- 2. INDIVIDUAL CHECKBOX LOGIC ---
  $(document).on("change", ".dedu-role-checkbox", function () {
    const $visibleCheckboxes = $(".dedu-role-checkbox:visible");
    if (
      $visibleCheckboxes.filter(":checked").length ===
        $visibleCheckboxes.length &&
      $visibleCheckboxes.length > 0
    ) {
      $("#dedu-select-all").prop("checked", true);
    } else {
      $("#dedu-select-all").prop("checked", false);
    }
  });

  // --- 3. BULK ACTION BUTTON --- (Keep as is)
  $("#dedu-apply-bulk-action").on("click", function () {
    const action = $("#dedu-bulk-action-selector").val();
    const selectedIds = $(".dedu-role-checkbox:checked")
      .map(function () {
        return $(this).val();
      })
      .get();

    if (selectedIds.length === 0 || !action) {
      alert("Please select both an action and at least one role.");
      return;
    }

    if (
      action === "delete" &&
      !confirm(`Are you sure you want to delete ${selectedIds.length} roles?`)
    )
      return;

    const $form = $("<form>", {
      action: ajaxurl.replace("admin-ajax.php", "admin-post.php"),
      method: "POST",
    })
      .append(
        $("<input>", {
          type: "hidden",
          name: "action",
          value: "dedu_bulk_action_roles",
        }),
      )
      .append(
        $("<input>", { type: "hidden", name: "bulk_action", value: action }),
      )
      .append(
        $("<input>", {
          type: "hidden",
          name: "role_ids",
          value: selectedIds.join(","),
        }),
      )
      .append(
        $("<input>", {
          type: "hidden",
          name: "dedu-role-nonce",
          value: $("#dedu-role-nonce").val(),
        }),
      );

    $("body").append($form);
    $form.submit();
  });

  // --- 4. SINGLE DELETE --- (Keep as is)
  $(document).on("click", ".dedu-delete-role", function (e) {
    e.preventDefault();
    const roleId = $(this).data("id");
    const roleName = $(this).data("name");
    const nonce = $(this).data("nonce");

    if (confirm(`Are you sure you want to delete the "${roleName}" role?`)) {
      const baseAdminUrl = ajaxurl.replace("admin-ajax.php", "admin-post.php");
      window.location.href = `${baseAdminUrl}?action=dedu_delete_role&id=${roleId}&_wpnonce=${nonce}`;
    }
  });

  // --- 5. PAGINATION CORE ENGINE ---
  function paginateTable() {
    const $rows = $(".dedu-table-modern tbody tr").not(
      "#dedu-no-search-results, .dedu-no-data-static",
    );

    const $filteredRows = $rows.filter(function () {
      return $(this).data("search-match") !== false;
    });

    const totalRows = $filteredRows.length;
    const totalPages = Math.ceil(totalRows / rowsPerPage);

    // Safety: If current page exceeds new total pages (after search/resize)
    if (currentPage > totalPages && totalPages > 0) currentPage = totalPages;
    if (currentPage < 1) currentPage = 1;

    $rows.hide();
    const start = (currentPage - 1) * rowsPerPage;
    const end = start + rowsPerPage;
    $filteredRows.slice(start, end).show();

    // Update URL and UI
    updateURL(currentPage);
    $("#total-visible-items").text(totalRows);
    $("#current-visible-range").text(
      totalRows > 0 ? `${start + 1}-${Math.min(end, totalRows)}` : "0-0",
    );

    renderPageNumbers(totalPages);
    $("#prev-page").prop("disabled", currentPage === 1 || totalRows === 0);
    $("#next-page").prop(
      "disabled",
      currentPage === totalPages || totalRows === 0 || totalPages === 1,
    );

    updateControlsState();
  }

  function renderPageNumbers(totalPages) {
    let html = "";
    if (totalPages > 1) {
      for (let i = 1; i <= totalPages; i++) {
        html += `<button type="button" class="page-num ${i === currentPage ? "active" : ""}" data-page="${i}">${i}</button>`;
      }
    }
    $("#page-numbers").html(html);
  }

  // Rows Per Page Change
  $("#dedu-rows-per-page").on("change", function () {
    rowsPerPage = parseInt($(this).val());
    currentPage = 1;
    paginateTable();
  });

  $(document).on("click", ".page-num", function () {
    currentPage = parseInt($(this).data("page"));
    paginateTable();
  });

  $("#prev-page").on("click", function () {
    if (currentPage > 1) {
      currentPage--;
      paginateTable();
    }
  });

  $("#next-page").on("click", function () {
    currentPage++;
    paginateTable();
  });

  // --- 7. CONTROL STATE & SEARCH ---
  function updateControlsState() {
    const isDbEmpty = $(".dedu-no-data-static").is(":visible");
    const $rows = $(".dedu-table-modern tbody tr").not(
      "#dedu-no-search-results, .dedu-no-data-static",
    );
    const hasMatches =
      $rows.filter(function () {
        return $(this).data("search-match") !== false;
      }).length > 0;
    const shouldDisable = isDbEmpty || !hasMatches;

    $("#dedu-select-all").prop("disabled", shouldDisable);
    $("#dedu-bulk-action-selector").prop("disabled", shouldDisable);
    $("#dedu-apply-bulk-action").prop("disabled", shouldDisable);

    if (shouldDisable) $("#dedu-select-all").prop("checked", false);
  }

  $("#dedu-role-search").on("keyup", function () {
    const value = $(this).val().toLowerCase().trim();
    let matchCount = 0;
    const $rows = $(".dedu-table-modern tbody tr").not(
      "#dedu-no-search-results, .dedu-no-data-static",
    );

    $rows.each(function () {
      const roleName = $(this).find("td").eq(1).text().toLowerCase().trim();
      const match = roleName.startsWith(value);
      $(this).data("search-match", match);
      if (match) matchCount++;
    });

    currentPage = 1;
    $("#dedu-no-search-results").toggle(matchCount === 0 && value !== "");
    paginateTable();
  });

  paginateTable();
});