// routes/bookRoutes.ts
import { Router } from "express";
import {
  addBookValidator,
  updateBookValidator,
  patchBookValidator,
} from "../../validators/bookValidator";
import {
  createBook,
  updateBook,
  patchBook,
  deleteBook,
  getAllBooks,
  getBookById,
} from "./controllers/bookController";
import { authenticateToken } from "../../middlewares/authMiddleware";
import { bookUpload } from "../../middlewares/upload";

const router = Router();

// POST: Add a new book
router.post(
  "/add",
  authenticateToken,
  bookUpload.fields([
    { name: "coverImage", maxCount: 1 },
    { name: "file", maxCount: 1 },
  ]),
  addBookValidator,
  createBook
);

// PUT: Full update
router.put(
  "/update/:id",
  authenticateToken,
  bookUpload.fields([
    { name: "coverImage", maxCount: 1 },
    { name: "file", maxCount: 1 },
  ]),
  updateBookValidator,
  updateBook
);

// PATCH: Partial update
router.patch(
  "/update/:id",
  authenticateToken,
  bookUpload.fields([
    { name: "coverImage", maxCount: 1 },
    { name: "file", maxCount: 1 },
  ]),
  patchBookValidator,
  patchBook
);

// DELETE: Delete book by ID
router.delete(
  "/delete/:id",
  authenticateToken,
  deleteBook
);

// GET: All books (with pagination, search, filter, etc.)
router.get(
  "/all",
  authenticateToken,
  getAllBooks
);

// GET: Book by ID
router.get(
  "/:id",
  authenticateToken,
  getBookById
);

export default router;