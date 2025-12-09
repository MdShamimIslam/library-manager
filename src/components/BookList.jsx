import { useState, useEffect } from 'react';
import { fetchBooks, deleteBook } from '../api';
import BookForm from './BookForm';
import ConfirmDelete from './ConfirmDelete';

const BookList = () => {
  const [books, setBooks] = useState([]);
  const [editingBook, setEditingBook] = useState(null);
  const [deletingBook, setDeletingBook] = useState(null);
  const [showForm, setShowForm] = useState(false);

  const loadBooks = async () => {
    const data = await fetchBooks();
    setBooks(data);
  };

  useEffect(() => { loadBooks(); }, []);

  const handleDelete = async (id) => {
    await deleteBook(id);
    setDeletingBook(null);
    loadBooks();
  };

  return (
    <div>
      <button className="lm-btn lm-btn-add" onClick={() => { setEditingBook(null); setShowForm(true); }}>Add New Book</button>

      {showForm && (
        <BookForm
          bookId={editingBook}
          onSuccess={() => { setShowForm(false); loadBooks(); }}
          onCancel={() => setShowForm(false)}
        />
      )}

      {deletingBook && (
        <ConfirmDelete
          onConfirm={() => handleDelete(deletingBook)}
          onCancel={() => setDeletingBook(null)}
        />
      )}

      <table className="lm-table">
        <thead>
          <tr>
            <th>Title</th><th>Author</th><th>Year</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          {books.map(b => (
            <tr key={b.id}>
              <td>{b.title}</td>
              <td>{b.author}</td>
              <td>{b.publication_year}</td>
              <td>{b.status}</td>
              <td>
                <button className="lm-btn lm-btn-edit" onClick={() => { setEditingBook(b.id); setShowForm(true); }}>Edit</button>
                <button className="lm-btn lm-btn-delete" onClick={() => setDeletingBook(b.id)}>Delete</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
};

export default BookList;
