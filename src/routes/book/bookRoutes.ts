// routes/bookRoutes.ts
import { Router } from "express";
import { addBookValidator } from "../../validators/bookValidator";
import { createBook } from "./controllers/bookController";
import { bookUpload } from "../../middlewares/upload";

const router = Router();

// Use centralized book upload middleware
router.post(
  "/add",
  bookUpload.fields([
    { name: "coverImage", maxCount: 1 },
    { name: "file", maxCount: 1 },
  ]),
  addBookValidator,
  createBook
);

export default router;
