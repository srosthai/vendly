import { Head } from '@inertiajs/react';
import BrandController from '@/actions/App/Http/Controllers/BrandController';
import { Tag } from 'lucide-react';
import { NamedList } from '@/components/vendor/named-list';
import type { NamedRecord } from '@/components/vendor/named-list';
import vendor from '@/routes/vendor';

export default function Brands({ brands }: { brands: NamedRecord[] }) {
    return (
        <>
            <Head title="Brands" />
            <NamedList
                title="Brands"
                noun="Brand"
                icon={Tag}
                description="Show who makes each product."
                items={brands}
                routes={{
                    store: BrandController.store.form(),
                    update: (id) => BrandController.update.form(id),
                    destroy: (id) => BrandController.destroy.form(id),
                }}
            />
        </>
    );
}

Brands.layout = {
    breadcrumbs: [{ title: 'Brands', href: vendor.brands() }],
};
