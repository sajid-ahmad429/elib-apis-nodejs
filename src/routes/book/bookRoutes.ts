// routes/bookRoutes.ts
import { Router } from "express";
import { addBookValidator } from "../../validators/bookValidator";
import { createBook } from "./controllers/bookController";
import { bookUpload } from "../../middlewares/upload";
import { authenticateToken } from "../../middlewares/authMiddleware"; // <- Import the auth middleware

const router = Router();

// Secure route to add a book
router.post(
  "/add",
  authenticateToken, // <- Authenticate user before proceeding
  bookUpload.fields([
    { name: "coverImage", maxCount: 1 },
    { name: "file", maxCount: 1 },
  ]),
  addBookValidator,
  createBook
);

export default router;
