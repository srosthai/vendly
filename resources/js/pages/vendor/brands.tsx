import { Head } from '@inertiajs/react';
import { NamedList } from '@/pages/vendor/categories';

export default function Brands({
    brands,
}: {
    brands: { id: number; name: string }[];
}) {
    return (
        <>
            <Head title="Brands" />
            <NamedList
                title="Brands"
                empty="No brands yet."
                action="/brands"
                items={brands}
            />
        </>
    );
}
