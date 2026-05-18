import Alpine from 'alpinejs';
import DataTable from 'datatables.net-dt';
import 'datatables.net-dt/css/dataTables.dataTables.css';

window.Alpine = Alpine;
window.DataTable = DataTable;

let pdfjsPromise;

const chargerPdfJs = async () => {
    if (! pdfjsPromise) {
        pdfjsPromise = Promise.all([
            import('pdfjs-dist'),
            import('pdfjs-dist/build/pdf.worker.min.mjs?url'),
        ]).then(([pdfjsLib, pdfWorker]) => {
            pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorker.default;

            return pdfjsLib;
        });
    }

    return pdfjsPromise;
};

window.createJustificatifViewer = () => ({
    justificatifModalOpen: false,
    justificatifActif: null,
    justificatifZoom: 1,
    justificatifLoading: false,
    justificatifError: '',
    pdfDocument: null,
    renderSequence: 0,
    minZoom: 0.5,
    maxZoom: 2.5,
    zoomStep: 0.25,

    async ouvrirJustificatif(fichier) {
        this.justificatifActif = fichier;
        this.justificatifModalOpen = true;
        this.justificatifZoom = 1;
        this.justificatifError = '';
        this.pdfDocument = null;
        this.renderSequence += 1;

        await this.$nextTick();
        await new Promise((resolve) => {
            window.requestAnimationFrame(() => window.requestAnimationFrame(resolve));
        });

        if (fichier.mimeType === 'application/pdf') {
            await this.chargerPdf();
        }
    },

    fermerJustificatif() {
        this.justificatifModalOpen = false;
        this.justificatifActif = null;
        this.justificatifZoom = 1;
        this.justificatifLoading = false;
        this.justificatifError = '';
        this.pdfDocument = null;
        this.renderSequence += 1;
    },

    async ajusterJustificatif() {
        this.justificatifZoom = 1;

        if (this.justificatifActif?.mimeType === 'application/pdf' && this.pdfDocument) {
            await this.rendrePdf();
        }
    },

    async zoomerJustificatif(delta) {
        this.justificatifZoom = Math.min(
            this.maxZoom,
            Math.max(this.minZoom, Number((this.justificatifZoom + delta).toFixed(2))),
        );

        if (this.justificatifActif?.mimeType === 'application/pdf' && this.pdfDocument) {
            await this.rendrePdf();
        }
    },

    async chargerPdf() {
        this.justificatifLoading = true;
        this.justificatifError = '';

        try {
            const pdfjsLib = await chargerPdfJs();
            const loadingTask = pdfjsLib.getDocument(this.justificatifActif.url);
            this.pdfDocument = await loadingTask.promise;
            await this.rendrePdf();
        } catch (error) {
            console.error('Échec du chargement PDF', error);
            this.justificatifError = 'Impossible de charger ce document.';
        } finally {
            this.justificatifLoading = false;
        }
    },

    async rendrePdf() {
        const pdfDocument = Alpine.raw(this.pdfDocument);

        if (! pdfDocument || ! this.$refs.pdfPages || ! this.$refs.viewerViewport) {
            return;
        }

        const currentSequence = ++this.renderSequence;
        const pagesContainer = this.$refs.pdfPages;
        const availableWidth = Math.max(this.$refs.viewerViewport.clientWidth - 32, 240);

        pagesContainer.replaceChildren();

        for (let pageNumber = 1; pageNumber <= pdfDocument.numPages; pageNumber += 1) {
            if (currentSequence !== this.renderSequence) {
                return;
            }

            const page = await pdfDocument.getPage(pageNumber);
            const initialViewport = page.getViewport({ scale: 1 });
            const fitWidthScale = availableWidth / initialViewport.width;
            const viewport = page.getViewport({ scale: fitWidthScale * this.justificatifZoom });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            const outputScale = window.devicePixelRatio || 1;

            canvas.width = Math.floor(viewport.width * outputScale);
            canvas.height = Math.floor(viewport.height * outputScale);
            canvas.style.width = `${Math.floor(viewport.width)}px`;
            canvas.style.height = `${Math.floor(viewport.height)}px`;
            canvas.className = 'block bg-white shadow-lg';

            pagesContainer.append(canvas);

            await page.render({
                canvasContext: context,
                transform: outputScale !== 1 ? [outputScale, 0, 0, outputScale, 0, 0] : null,
                viewport,
            }).promise;
        }
    },
});

Alpine.start();
