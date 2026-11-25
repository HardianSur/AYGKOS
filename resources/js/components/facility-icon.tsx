import {
    AirVent,
    Armchair,
    Bath,
    Bed,
    Building2,
    Car,
    Clock,
    Fan,
    Lamp,
    Refrigerator,
    ShieldCheck,
    Shirt,
    Snowflake,
    Sofa,
    Table,
    Tv,
    Utensils,
    Video,
    WashingMachine,
    Waves,
    Wifi,
    type LucideIcon,
} from 'lucide-react';

export const facilityIconNames = [
    'building-2',
    'wifi',
    'car',
    'shield-check',
    'utensils',
    'sofa',
    'washing-machine',
    'video',
    'snowflake',
    'bath',
    'bed',
    'tv',
    'lamp',
    'fan',
    'refrigerator',
    'air-vent',
    'shirt',
    'table',
    'armchair',
    'clock',
    'waves',
] as const;

export type FacilityIconName = (typeof facilityIconNames)[number];

const icons: Record<FacilityIconName, LucideIcon> = {
    'building-2': Building2,
    wifi: Wifi,
    car: Car,
    'shield-check': ShieldCheck,
    utensils: Utensils,
    sofa: Sofa,
    'washing-machine': WashingMachine,
    video: Video,
    snowflake: Snowflake,
    bath: Bath,
    bed: Bed,
    tv: Tv,
    lamp: Lamp,
    fan: Fan,
    refrigerator: Refrigerator,
    'air-vent': AirVent,
    shirt: Shirt,
    table: Table,
    armchair: Armchair,
    clock: Clock,
    waves: Waves,
};

export function FacilityIcon({
    name,
    className,
}: {
    name: string;
    className?: string;
}) {
    const Icon = (icons as Record<string, LucideIcon>)[name];

    return Icon ? <Icon aria-hidden="true" className={className} /> : null;
}
