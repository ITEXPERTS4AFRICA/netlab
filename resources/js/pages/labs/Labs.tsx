import AppLayout from '@/layouts/app-layout';
import { labs} from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, usePage } from '@inertiajs/react';
import { Card, CardTitle, CardDescription, CardContent, CardFooter } from '@/components/ui/card';
import { PaginationApp } from '@/components/app-pagination';
import LabReservationDialog from '@/components/lab-reservation-dialog';
import { Button } from '@/components/ui/button';
import {
        OctagonAlert,
        Octagon,
        Calendar
    }
from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Labs',
        href: labs().url,
    }
];

type Lab = {
    id: string;
    state: string;
    lab_title: string;
    node_count: string|number;
    lab_description: string;
    created: string;
    modified: string;
};

type Pagination = {
    page: number;
    per_page: number;
    total: number;
    total_pages: number;
};

type Props = {
    labs: Lab[];
    pagination: Pagination;
};

export default function Labs() {
    const { labs, pagination } = usePage<Props>().props;



    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Labs" />
            <div className="container flex h-full flex-1 flex-col gap-4 overflow-y-auto rounded-xl p-4">
                <h1 className="text-2xl font-semibold">Labs</h1>
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    {labs.map((lab) => (
                        <Card key={lab.id} className="p-4">
                            <CardTitle className="text-lg flex justify-between  items-center ">
                                <span >{lab.lab_title}</span>
                                <p className='flex items-center '>
                                   Status: {lab.state === 'STOPPED' ?  <OctagonAlert className="text-red-500 h-5" /> :  <Octagon className="text-green-500 h-5" />}
                                </p>
                            </CardTitle>
                            <CardContent>
                                <p className="text-sm text-gray-100 mt-2">
                                    Node: {lab.node_count}
                                </p>
                            </CardContent>
                            <CardDescription>
                                {lab.lab_description}
                            </CardDescription>
                            <CardFooter className="flex justify-between items-center">
                                <p className="text-sm text-gray-200">
                                    modified: {lab.modified}{` `}Created: {lab.created}
                                </p>
                                <LabReservationDialog
                                    lab={{
                                        id: lab.id,
                                        title: lab.lab_title,
                                        description: lab.lab_description,
                                        state: lab.state
                                    }}
                                >

                                    <Button variant="default" size="sm">
                                        <Calendar className="h-4 w-4 mr-2" />
                                        Book Now
                                    </Button>
                                </LabReservationDialog>
                            </CardFooter>
                        </Card>
                    ))}
                </div>
                {pagination.total_pages > 1 && (
                    <div className="flex justify-center mt-4">
                        <PaginationApp
                            page={pagination.page}
                            per_page={pagination.per_page}
                            total={pagination.total}
                            total_pages={pagination.total_pages}
                        />
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
