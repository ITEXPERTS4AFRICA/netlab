<?

namespace App\Interfaces;

interface UsersInterfaces
{
    public function get($id);
    public function create($data);
    public function update($id, $data);
    public function delete($id);
}
