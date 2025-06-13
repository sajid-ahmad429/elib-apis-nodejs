import { Router } from "express";
import { addBookValidator } from "../../validators/bookValidator";
import { createBook } from "./controllers/bookController";

const router = Router();

router.post("/add", addBookValidator, createBook);

export default router;
