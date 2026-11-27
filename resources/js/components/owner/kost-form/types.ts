export type Facility = {
    id: string;
    name: string;
    icon: string;
};

export type Rule = {
    id: string;
    name: string;
};

export type KostType = 'putra' | 'putri' | 'campur';

export type DocumentKey =
    | 'nib'
    | 'surat_pernyataan'
    | 'domisili_usaha'
    | 'izin_lingkungan'
    | 'izin_kelurahan';

export type Documents = Record<DocumentKey, File | null>;

export type KostForm = {
    name: string;
    address: string;
    city: string;
    latitude: string;
    longitude: string;
    type: KostType;
    description: string;
    facilities: string[];
    rules: string[];
    photos: File[];
    documents: Documents;
};

export const emptyDocuments: Documents = {
    nib: null,
    surat_pernyataan: null,
    domisili_usaha: null,
    izin_lingkungan: null,
    izin_kelurahan: null,
};

export const optionalDocuments: { key: DocumentKey; label: string }[] = [
    { key: 'domisili_usaha', label: 'Surat keterangan domisili usaha' },
    { key: 'izin_lingkungan', label: 'Izin lingkungan RT/RW' },
    { key: 'izin_kelurahan', label: 'Izin kelurahan/kecamatan' },
];

export const kostTypes: { value: KostType; label: string; hint: string }[] = [
    { value: 'putra', label: 'Putra', hint: 'Hanya menerima penghuni putra.' },
    { value: 'putri', label: 'Putri', hint: 'Hanya menerima penghuni putri.' },
    {
        value: 'campur',
        label: 'Campur',
        hint: 'Menerima penghuni putra dan putri.',
    },
];
