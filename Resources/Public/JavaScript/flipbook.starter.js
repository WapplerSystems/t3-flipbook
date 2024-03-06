if (typeof flipbookOptions != 'undefined') {
  if ($(flipbookOptions).length > 0) {
    $.each(flipbookOptions, function (k, v) {
      //$("#container_"+k).addClass('loading');
      $("#container_" + k).flipBook(v);
      var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutationRecord) {
          //$(mutationRecord.target).removeClass('loading');
        });
      });

      var target = document.getElementById("container_" + k);
      observer.observe(target, {attributes: true, attributeFilter: ['style']});

    })
  }
}
