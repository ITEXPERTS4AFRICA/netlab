import { useState, useCallback } from 'react';
import { LabAnnotation } from '@/types/annotation';
import axios from 'axios';

export const useAnnotations = (labId: string) => {
  const [annotations, setAnnotations] = useState<LabAnnotation[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchAnnotations = useCallback(async () => {
    if (!labId) return;

    try {
      setLoading(true);
      setError(null);

      const response = await axios.get(`/labs/${labId}/annotations`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        withCredentials: true,
      });

      if (response.data && Array.isArray(response.data)) {
        setAnnotations(response.data);
      }
    } catch (err) {
      console.error('Error fetching annotations:', err);
      setError(axios.isAxiosError(err) ? err.message : 'Failed to load annotations');
    } finally {
      setLoading(false);
    }
  }, [labId]);

  const createAnnotation = useCallback(async (annotation: Omit<LabAnnotation, 'id'>) => {
    try {
      const response = await axios.post(`/labs/${labId}/annotations`, annotation, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        withCredentials: true,
      });

      if (response.data) {
        setAnnotations(prev => [...prev, response.data]);
        return response.data;
      }
    } catch (err) {
      console.error('Error creating annotation:', err);
      throw err;
    }
  }, [labId]);

  const updateAnnotation = useCallback(async (id: string, updates: Partial<LabAnnotation>) => {
    try {
      const response = await axios.patch(`/labs/${labId}/annotations/${id}`, updates, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        withCredentials: true,
      });

      if (response.data) {
        setAnnotations(prev => prev.map(ann =>
          ann.id === id ? response.data : ann
        ));
        return response.data;
      }
    } catch (err) {
      console.error('Error updating annotation:', err);
      throw err;
    }
  }, [labId]);

  const deleteAnnotation = useCallback(async (id: string) => {
    try {
      await axios.delete(`/labs/${labId}/annotations/${id}`, {
        headers: {
          'Accept': 'application/json',
          'Content-Type': 'application/json',
        },
        withCredentials: true,
      });

      setAnnotations(prev => prev.filter(ann => ann.id !== id));
    } catch (err) {
      console.error('Error deleting annotation:', err);
      throw err;
    }
  }, [labId]);

  return {
    annotations,
    loading,
    error,
    fetchAnnotations,
    createAnnotation,
    updateAnnotation,
    deleteAnnotation,
  };
};
