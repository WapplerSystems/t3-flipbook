

# Flipbook

> [!IMPORTANT]
> **Successor available:** for new projects use
> [**PDF Flip Book (`ws_pdflip`)**](https://extensions.typo3.org/extension/ws_pdflip) for TYPO3 13.4 and 14:
> `composer require wapplersystems/pdflip` — source: [WapplerSystems/t3-pdflip](https://github.com/WapplerSystems/t3-pdflip).
> It is based on the open-source library [pdflip](https://github.com/WapplerSystems/pdflip) (pdf.js, no jQuery)
> and adds search, selectable text, clickable PDF links, a lightbox gallery and accessible controls.
> Flip books from an image folder are not supported there — it works with PDF files.

The Flipbook for PDFs TYPO3 extension allows you to create an interactive flipbook view of your PDF documents directly on your website.

## Convert PDF into a flipbook

##### 1. Create a Folder for PDFs:

Navigate to **Filelist** in your TYPO3 backend.
Create a new folder where you want to store the PDF files used for the flipbook and store your PDF.

##### 2. Create a Flipbook Page:

In the Page Tree, create a new page, include TypoScript of this extension.

Then add a content element of type **Flipbook [flipbook_show]**.

##### 3. Upload and Select Your PDF:

Under **Content-type options** in the **General**, locate PDF file for the download link.
Select an existing one from the folder you created in Step 1.

##### 4. Configure the Flipbook Options:

Go to the **Options**. Select **Create Flipbook from PDF[PDF]** in **Select a plug-in mode**.
Under **Select the PDF file where the pages are created**, click on **File** and select your PDF file to define the pages in the flipbook. **Note: It is not yet possible to upload only selected pages of a PDF.**

##### 5. Save and Refresh:

Click Save to store your settings.
Refresh your website to see the PDF displayed as an interactive flipbook!


## Convert lots of photos into a flipbook
The same goes for Folders to Flipbook. The only difference is that you should create a folder where you can upload the photos and select **Create Flipbook from folders[folders]** in **Select a plug-in mode**.


## Options

* **Show the book opened (without lightbox):** the book is shown in the content element instead of a preview image that opens the lightbox.
  **Show wide** then adds the CSS class `flipbook-wide` to the book container, the width is up to the theme.
* **Title:** caption of the preview image, the description of the image is used if it is empty.
* **Enable search in the book:** adds a search button to the menu (PDF mode only).

The PDF file is selected from the file list. Values of older versions stored as `t3://file?uid=…` or as a path like
`fileadmin/book.pdf` still work in the frontend, but the backend shows the field empty and saving the content element
removes the PDF. Run the upgrade wizard **Flipbook: store the sys_file uid in the PDF field** after the update, it
replaces these values with the uid of the file and lists the content elements whose value is not a file.
External URLs are not supported.

## Page rendering in PDF mode

The pages are rendered by pdf.js in the browser. The TypoScript constants `plugin.tx_flipbook.settings.*` control the view
mode and the resolution of the rendered pages. If they are empty, the defaults of the flipbook library are used.

| Constant | Empty | Recommended | Description |
|---|---|---|---|
| `viewMode` | `webgl` | `2d` | `2d` renders the text sharper than `webgl` |
| `pageTextureSize` | `2048` | `2048` | Height of the rendered pages in pixels |
| `pageTextureSizeMobile` | `pageTextureSize` | `1024` | Height of the rendered pages on mobile devices |
| `pageTextureSizeMax` | off | `4096` | In the `2d` and `3d` view the pages are rendered in their displayed height times the device pixel ratio, up to this height (on mobile devices up to `pageTextureSizeMobile`), and rendered again after zooming or resizing |

```typoscript
plugin.tx_flipbook.settings {
    viewMode = 2d
    pageTextureSize = 2048
    pageTextureSizeMobile = 1024
    pageTextureSizeMax = 4096
}
```
