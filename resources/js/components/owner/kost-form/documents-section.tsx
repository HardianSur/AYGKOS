import type { InertiaFormProps } from '@inertiajs/react';
import { DocumentField } from './document-field';
import { FormSection } from './form-section';
import { optionalDocuments, type KostForm } from './types';

export function DocumentsSection({
    form,
}: {
    form: InertiaFormProps<KostForm>;
}) {
    const { setData } = form;

    return (
        <FormSection
            title="Dokumen izin"
            description="Dokumen berformat PDF dengan ukuran maksimal 5 MB per berkas."
        >
            <div className="grid gap-5 sm:grid-cols-2">
                <DocumentField
                    id="document-nib"
                    label="NIB"
                    required
                    file={form.data.documents.nib}
                    error={form.errors['documents.nib']}
                    onChange={(file) =>
                        setData('documents', {
                            ...form.data.documents,
                            nib: file,
                        })
                    }
                />

                <DocumentField
                    id="document-surat-pernyataan"
                    label="Surat pernyataan pemilik bangunan"
                    required
                    file={form.data.documents.surat_pernyataan}
                    error={form.errors['documents.surat_pernyataan']}
                    onChange={(file) =>
                        setData('documents', {
                            ...form.data.documents,
                            surat_pernyataan: file,
                        })
                    }
                />

                {optionalDocuments.map((document) => (
                    <DocumentField
                        key={document.key}
                        id={`document-${document.key}`}
                        label={document.label}
                        file={form.data.documents[document.key]}
                        error={form.errors[`documents.${document.key}`]}
                        onChange={(file) =>
                            setData('documents', {
                                ...form.data.documents,
                                [document.key]: file,
                            })
                        }
                    />
                ))}
            </div>
        </FormSection>
    );
}
