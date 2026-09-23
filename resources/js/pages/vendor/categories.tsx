import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import { NamedList } from '@/components/vendor/named-list';
import type { NamedRecord } from '@/components/vendor/named-list';

export default function Categories({
    categories,
}: {
    categories: NamedRecord[];
}) {
    return (
        <>
            <Head title="Categories" />
            <NamedList
                title="Categories"
                noun="Category"
                description="Customers filter your store by category."
                items={categories}
                routes={{
                    store: CategoryController.store.form(),
                    update: (id) => CategoryController.update.form(id),
                    destroy: (id) => CategoryController.destroy.form(id),
                }}
            />
        </>
    );
}
