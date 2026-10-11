

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

In case your site doesn't include jQuery already, set one of the following TS constants to 1:

* `plugin.tx_flipbook.settings.includeJqueryHeader`
* `plugin.tx_flipbook.settings.includeJqueryFooter`

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


## Changelog

### 12.0.5

* No more "Undefined array key" warnings when settings are missing ([#12](https://github.com/WapplerSystems/t3-flipbook/issues/12)).
* PDF mode without a file no longer throws an exception; no preview image without a file ([#4](https://github.com/WapplerSystems/t3-flipbook/issues/4)).
* `t3://file?uid=…` in the PDF and download fields is resolved to the public URL; download buttons are only shown when a URL is set ([#9](https://github.com/WapplerSystems/t3-flipbook/issues/9)).
* External PDF URLs are passed through.

### 12.0.4

* Correct icon for the "last page" button ([#7](https://github.com/WapplerSystems/t3-flipbook/issues/7), [#8](https://github.com/WapplerSystems/t3-flipbook/pull/8)).
* README: the flipbook is a content element, not a page type; jQuery constants ([#6](https://github.com/WapplerSystems/t3-flipbook/pull/6), [#10](https://github.com/WapplerSystems/t3-flipbook/issues/10)).

## Successor for TYPO3 13 and 14

Version 12 only receives bug fixes. For TYPO3 13.4 and 14 use
**PDF Flip Book (`ws_pdflip`)**:

* TER: https://extensions.typo3.org/extension/ws_pdflip
* Composer: `composer require wapplersystems/pdflip`
* Extension: https://github.com/WapplerSystems/t3-pdflip
* Open-source flip book library it is built on: https://github.com/WapplerSystems/pdflip
