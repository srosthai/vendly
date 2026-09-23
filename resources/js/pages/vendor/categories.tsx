import { Head } from '@inertiajs/react';
import CategoryController from '@/actions/App/Http/Controllers/CategoryController';
import { Shapes } from 'lucide-react';
import { NamedList } from '@/components/vendor/named-list';
import type { NamedRecord } from '@/components/vendor/named-list';
import type { Paginated } from '@/components/simple-pagination';
import vendor from '@/routes/vendor';

export default function Categories({
    categories,
    filters,
}: {
    categories: Paginated<NamedRecord & { products_count: number }>;
    filters: { search: string; sort: string };
}) {
    return (
        <>
            <Head title="Categories" />
            <NamedList
                title="Categories"
                noun="Category"
                icon={Shapes}
                description="Customers filter your store by category."
                items={categories}
                filters={filters}
                url={vendor.categories.url()}
                defaultSortLabel="Your order"
                routes={{
                    store: CategoryController.store.form(),
                    update: (id) => CategoryController.update.form(id),
                    destroy: (id) => CategoryController.destroy.form(id),
                }}
            />
        </>
    );
}

Categories.layout = {
    breadcrumbs: [{ title: 'Categories', href: vendor.categories() }],
};
