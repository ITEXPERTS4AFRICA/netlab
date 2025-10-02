import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import { router } from '@inertiajs/react'

interface PaginationProps {
  page?: number
  per_page?: number
  total?: number
  total_pages?: number
}

const handlePageChange = (newPage: number, totalPages: number) => {
  if (newPage >= 1 && newPage <= totalPages) {
    router.visit(window.location.pathname, {
      data: { page: newPage },
      preserveState: true,
      replace: true,
    });
  }
};

// Utility function to generate page numbers with ellipsis
function generatePageItems(currentPage: number, totalPages: number): (number | string)[] {
  const pages: (number | string)[] = [];
  const maxDisplayedPages = 7;

  if (totalPages <= maxDisplayedPages) {
    // Show all pages if total is small
    for (let i = 1; i <= totalPages; i++) {
      pages.push(i);
    }
  } else {
    // Always show first page
    pages.push(1);

    // Calculate range around current page
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(totalPages - 1, currentPage + 1);

    // Add ellipsis before if there's a gap
    if (start > 2) {
      pages.push('...');
    }

    // Add middle pages
    for (let i = start; i <= end; i++) {
      pages.push(i);
    }

    // Add ellipsis after if there's a gap
    if (end < totalPages - 1) {
      pages.push('...');
    }

    // Always show last page
    pages.push(totalPages);
  }

  return pages;
}

export function PaginationApp({ page = 1, total_pages = 1 }: PaginationProps) {
  const isPreviousEnabled = page > 1;
  const isNextEnabled = page < total_pages;

  const pageItems = generatePageItems(page, total_pages);

  return (
    <Pagination>
      <PaginationContent>
        <PaginationItem>
          <PaginationPrevious
            onClick={() => isPreviousEnabled && handlePageChange(page - 1, total_pages)}
            aria-disabled={!isPreviousEnabled}
            className={!isPreviousEnabled ? 'pointer-events-none opacity-50' : ''}
          />
        </PaginationItem>
        {pageItems.map((item, index) => (
          <PaginationItem key={index}>
            {typeof item === 'number' ? (
              <PaginationLink
                onClick={(e) => {
                  e.preventDefault();
                  handlePageChange(item, total_pages);
                }}
                isActive={item === page}
                className="cursor-pointer"
              >
                {item}
              </PaginationLink>
            ) : (
              <PaginationEllipsis />
            )}
          </PaginationItem>
        ))}
        <PaginationItem>
          <PaginationNext
            onClick={() => isNextEnabled && handlePageChange(page + 1, total_pages)}
            aria-disabled={!isNextEnabled}
            className={!isNextEnabled ? 'pointer-events-none opacity-50' : ''}
          />
        </PaginationItem>
      </PaginationContent>
    </Pagination>
  );
}
