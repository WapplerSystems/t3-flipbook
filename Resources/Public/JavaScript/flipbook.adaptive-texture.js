/**
 * Renders the pdf pages of the 2d and 3d view in the size they are displayed in.
 *
 * Without this, the pages are always rendered pageTextureSize pixels high and scaled by css.
 * Scaling them down a lot makes the text look frayed on standard displays, scaling them up
 * when zooming on high-dpi displays makes it blurry. The pages are rendered in the displayed
 * height times the device pixel ratio instead, rounded up to steps of 256 pixels and limited
 * to pageTextureSizeMax (and to pageTextureSizeMobile on mobile devices), and rendered again
 * after zooming or resizing. The library is only patched when a book sets pageTextureSizeMax.
 *
 * The book keeps its layout in pageTextureSize units, only the page canvases change their size.
 *
 * Written against the bundled flipbook.min.js v3.9.10. It patches internals of the library
 * (FLIPBOOK.Book3, FLIPBOOK.Page3, pagesArr, rightIndex, pdfService.canvasBuffer), check it
 * when updating the library.
 */
(function () {
  if (typeof FLIPBOOK === 'undefined' || !FLIPBOOK.Main || typeof jQuery === 'undefined' || !jQuery.fn.flipBook) {
    return;
  }

  var SIZE_STEP = 256;
  var UPDATE_DELAY = 300;

  function patchBook3() {
    if (!FLIPBOOK.Book3 || FLIPBOOK.Book3.prototype.updateTextureSize) {
      return;
    }

    var style = document.createElement('style');
    style.textContent = '.flipbook-page3-bg canvas{width:100%;height:100%}';
    document.head.appendChild(style);

    var pageLoad = FLIPBOOK.Page3.prototype.load;
    FLIPBOOK.Page3.prototype.load = function (callback) {
      var book = this.book;
      if (book.textureSize === undefined) {
        // the book is not scaled to its container yet when the first pages are loaded
        book.textureSize = book.getTextureSize(book.estimatePageHeight());
      }
      var size = book.textureSize;
      if (!size || this.loaded) {
        return pageLoad.call(this, callback);
      }
      // Page3.load reads the size from the options shared with the whole book
      var options = this.options;
      var pageTextureSize = options.pageTextureSize;
      var page = this;
      options.pageTextureSize = size;
      try {
        return pageLoad.call(this, function () {
          if (size !== book.textureSize) {
            // rendering an outdated size finished after the current one, show the current one again
            page.loaded = false;
            page.load();
          } else {
            releaseOtherSizes(book, page.index, size);
          }
          if (callback) {
            callback.apply(this, arguments);
          }
        });
      } finally {
        options.pageTextureSize = pageTextureSize;
      }
    };

    /**
     * @param {number} pageHeight displayed page height in css pixels
     * @return {number} the page texture height in device pixels, 0 to use pageTextureSize
     */
    FLIPBOOK.Book3.prototype.getTextureSize = function (pageHeight) {
      var options = this.options;
      var maxSize = options.pageTextureSizeMax || 0;
      if (maxSize <= 0 || !pageHeight) {
        return 0;
      }
      if (options.isMobile) {
        maxSize = Math.min(maxSize, options.pageTextureSize);
      }
      return Math.min(Math.ceil(pageHeight * (window.devicePixelRatio || 1) / SIZE_STEP) * SIZE_STEP, maxSize);
    };

    /**
     * The page height the book will be displayed with, it is fitted into the book layer and scaled by zoomMin.
     *
     * @return {number}
     */
    FLIPBOOK.Book3.prototype.estimatePageHeight = function () {
      var pagesPerView = this.options.singlePageMode ? 1 : 2;
      var height = Math.min(
        this.bookLayer.clientHeight,
        this.bookLayer.clientWidth / pagesPerView * this.pageHeight / this.pageWidth
      );
      return height * (this.options.zoomMin || 1);
    };

    FLIPBOOK.Book3.prototype.updateTextureSize = function () {
      var size = this.getTextureSize(this.wrapper.getBoundingClientRect().height);
      if (!size || size === this.textureSize) {
        return;
      }
      this.textureSize = size;
      for (var i = 0; i < this.pagesArr.length; i++) {
        this.pagesArr[i].loaded = false;
      }
      // the neighbouring pages are loaded in the new size as soon as the book needs them
      [this.pagesArr[this.rightIndex - 1], this.pagesArr[this.rightIndex]].forEach(function (page) {
        if (page) {
          page.load();
        }
      });
    };

    FLIPBOOK.Book3.prototype.scheduleTextureSizeUpdate = function () {
      var book = this;
      clearTimeout(this.textureSizeTimer);
      this.textureSizeTimer = setTimeout(function () {
        book.updateTextureSize();
      }, UPDATE_DELAY);
    };

    var bookResize = FLIPBOOK.Book3.prototype.onResize;
    FLIPBOOK.Book3.prototype.onResize = function () {
      var result = bookResize.apply(this, arguments);
      this.scheduleTextureSizeUpdate();
      return result;
    };
  }

  /**
   * The pdf service only frees canvases of pages far away from the current page,
   * so the canvases of the other sizes of a page would pile up while zooming.
   */
  function releaseOtherSizes(book, pageIndex, size) {
    var options = book.options;
    var pdfService = options.main && options.main.pdfService;
    if (!pdfService || options.doublePage) {
      return;
    }
    if (options.rightToLeft) {
      pageIndex = book.pagesArr.length - pageIndex - 1;
    }
    pdfService.canvasBuffer.forEach(function (canvas) {
      if (canvas.pageIndex !== pageIndex || canvas.size === size || canvas.rendering || !canvas.rendered) {
        return;
      }
      var pdfPage = pdfService.pages[canvas.pdfPageIndex];
      if (pdfPage && pdfPage.canvas) {
        delete pdfPage.canvas[canvas.size];
      }
      var page = options.pages[pageIndex];
      if (page && page.canvas) {
        delete page.canvas[canvas.size];
      }
      canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);
      canvas.width = canvas.height = 0;
      canvas.pageIndex = -100;
      canvas.available = true;
      canvas.rendered = false;
    });
  }

  var installed = false;

  function install() {
    if (installed) {
      return;
    }
    installed = true;

    // flipbook.book3.min.js is loaded on demand, patch it before the book is created
    var loadScript = FLIPBOOK.Main.prototype.loadScript;
    FLIPBOOK.Main.prototype.loadScript = function (src, callback) {
      return loadScript.call(this, src, function () {
        patchBook3();
        if (callback) {
          callback.apply(this, arguments);
        }
      });
    };

    var onZoom = FLIPBOOK.Main.prototype.onZoom;
    FLIPBOOK.Main.prototype.onZoom = function () {
      var result = onZoom.apply(this, arguments);
      if (this.Book && this.Book.scheduleTextureSizeUpdate) {
        this.Book.scheduleTextureSizeUpdate();
      }
      return result;
    };

    patchBook3();
  }

  var flipBook = jQuery.fn.flipBook;
  jQuery.fn.flipBook = function (options) {
    if (options && options.pageTextureSizeMax > 0) {
      install();
    }
    return flipBook.apply(this, arguments);
  };
  // the library reads its defaults from jQuery.fn.flipBook.options
  jQuery.extend(jQuery.fn.flipBook, flipBook);
})();
