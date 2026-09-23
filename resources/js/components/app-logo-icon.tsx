import type { ImgHTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

/**
 * The Vendly mark: the blue V with the orange cart. It keeps its own colors
 * on light and dark backgrounds.
 */
export default function AppLogoIcon({
    className,
    ...props
}: ImgHTMLAttributes<HTMLImageElement>) {
    return (
        <img
            src="/images/brand/vendly-mark-128.png"
            srcSet="/images/brand/vendly-mark-128.png 1x, /images/brand/vendly-mark.png 4x"
            alt=""
            aria-hidden="true"
            className={cn('shrink-0 object-contain', className)}
            {...props}
        />
    );
}
